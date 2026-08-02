<?php

declare(strict_types=1);

use Bambamboole\LaravelWebhooks\Models\WebhookDelivery;
use Bambamboole\LaravelWebhooks\Models\WebhookSubscription as SubscriptionModel;
use Carbon\Carbon;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Spatie\WebhookServer\CallWebhookJob;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

afterEach(function (): void {
    Carbon::setTestNow();
});

it('records succeeded webhook calls with their response status', function (): void {
    $subscription = SubscriptionModel::create([
        'url' => 'https://example.com/billing',
        'events' => ['invoice.paid'],
    ]);

    event(webhookCallEvent(WebhookCallSucceededEvent::class, [
        'meta' => [
            'event' => 'invoice.paid',
            'subscription_id' => $subscription->id,
            'payload_id' => 'payload-uuid',
        ],
        'response' => new Response(201),
    ]));

    $delivery = WebhookDelivery::sole();

    expect($delivery->id)->toBeUuid()
        ->and($delivery->subscription_id)->toBe($subscription->id)
        ->and($delivery->subscription?->url)->toBe('https://example.com/billing')
        ->and($delivery->call_uuid)->toBe('call-uuid')
        ->and($delivery->event)->toBe('invoice.paid')
        ->and($delivery->url)->toBe('https://example.com/webhooks')
        ->and($delivery->http_verb)->toBe('post')
        ->and($delivery->payload)->toBe(['event' => 'invoice.paid', 'data' => ['invoiceId' => 987]])
        ->and($delivery->attempt)->toBe(1)
        ->and($delivery->status)->toBe(WebhookDelivery::STATUS_SUCCEEDED)
        ->and($delivery->response_status)->toBe(201)
        ->and($delivery->error_type)->toBeNull()
        ->and($delivery->error_message)->toBeNull();
});

it('records failed webhook calls with their error details', function (): void {
    event(webhookCallEvent(WebhookCallFailedEvent::class, [
        'attempt' => 3,
        'response' => new Response(500),
        'errorType' => ServerException::class,
        'errorMessage' => 'Server error: 500',
    ]));

    $delivery = WebhookDelivery::sole();

    expect($delivery->status)->toBe(WebhookDelivery::STATUS_FAILED)
        ->and($delivery->attempt)->toBe(3)
        ->and($delivery->response_status)->toBe(500)
        ->and($delivery->error_type)->toBe(ServerException::class)
        ->and($delivery->error_message)->toBe('Server error: 500');
});

it('records failed webhook calls without a response', function (): void {
    event(webhookCallEvent(WebhookCallFailedEvent::class, [
        'response' => null,
        'errorType' => ConnectException::class,
        'errorMessage' => 'Connection refused',
    ]));

    expect(WebhookDelivery::sole()->response_status)->toBeNull();
});

it('marks the last failed delivery as final failed when retries are exhausted', function (): void {
    event(webhookCallEvent(WebhookCallFailedEvent::class, [
        'attempt' => 3,
        'errorType' => ServerException::class,
        'errorMessage' => 'Server error: 500',
    ]));
    event(webhookCallEvent(FinalWebhookCallFailedEvent::class, [
        'attempt' => 3,
        'errorType' => ServerException::class,
        'errorMessage' => 'Server error: 500',
    ]));

    $delivery = WebhookDelivery::sole();

    expect($delivery->status)->toBe(WebhookDelivery::STATUS_FINAL_FAILED)
        ->and($delivery->attempt)->toBe(3);
});

it('ignores spatie webhook calls not dispatched by this package', function (): void {
    event(webhookCallEvent(WebhookCallSucceededEvent::class, [
        'meta' => ['origin' => 'somewhere-else'],
    ]));

    expect(WebhookDelivery::count())->toBe(0);
});

it('resends a delivery using the current subscription configuration', function (): void {
    Bus::fake();

    $subscription = SubscriptionModel::create([
        'url' => 'https://example.com/billing-v2',
        'secret' => 'rotated-secret',
        'headers' => ['X-Tenant' => 'acme'],
        'events' => ['invoice.paid'],
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'call_uuid' => 'call-uuid',
        'event' => 'invoice.paid',
        'url' => 'https://example.com/billing-v1',
        'http_verb' => 'post',
        'payload' => ['id' => 'payload-uuid', 'event' => 'invoice.paid', 'data' => ['invoiceId' => 987]],
        'attempt' => 3,
        'status' => WebhookDelivery::STATUS_FINAL_FAILED,
    ]);

    $delivery->resend();

    Bus::assertDispatched(CallWebhookJob::class, function (CallWebhookJob $job) use ($subscription): bool {
        expect($job->webhookUrl)->toBe('https://example.com/billing-v2')
            ->and($job->payload)->toBe(['id' => 'payload-uuid', 'event' => 'invoice.paid', 'data' => ['invoiceId' => 987]])
            ->and($job->headers)->toMatchArray(['X-Tenant' => 'acme'])
            ->and($job->headers)->toHaveKey('Signature')
            ->and($job->meta)->toMatchArray([
                'event' => 'invoice.paid',
                'subscription_id' => $subscription->id,
                'payload_id' => 'payload-uuid',
            ]);

        return true;
    });
});

it('refuses to resend a delivery without a subscription', function (): void {
    Bus::fake();

    $delivery = WebhookDelivery::create([
        'call_uuid' => 'call-uuid',
        'event' => 'invoice.paid',
        'url' => 'https://example.com/webhooks',
        'http_verb' => 'post',
        'payload' => ['event' => 'invoice.paid'],
        'attempt' => 1,
        'status' => WebhookDelivery::STATUS_FAILED,
    ]);

    expect(fn () => $delivery->resend())
        ->toThrow(RuntimeException::class, "Cannot resend webhook delivery [{$delivery->id}] without its subscription");

    Bus::assertNothingDispatched();
});

it('prunes deliveries older than the configured retention', function (): void {
    Carbon::setTestNow(now()->subDays(40));
    event(webhookCallEvent(WebhookCallSucceededEvent::class));
    Carbon::setTestNow();
    event(webhookCallEvent(WebhookCallSucceededEvent::class));

    Artisan::call('model:prune', ['--model' => [WebhookDelivery::class]]);

    expect(WebhookDelivery::count())->toBe(1)
        ->and(WebhookDelivery::sole()->created_at?->isAfter(now()->subDay()))->toBeTrue();
});

it('keeps all deliveries when pruning is disabled', function (): void {
    config()->set('webhooks.deliveries.prune_after_days');

    Carbon::setTestNow(now()->subDays(400));
    event(webhookCallEvent(WebhookCallSucceededEvent::class));
    Carbon::setTestNow();

    Artisan::call('model:prune', ['--model' => [WebhookDelivery::class]]);

    expect(WebhookDelivery::count())->toBe(1);
});

/**
 * @param  class-string<WebhookCallEvent>  $eventClass
 * @param  array<string, mixed>  $overrides
 */
function webhookCallEvent(string $eventClass, array $overrides = []): WebhookCallEvent
{
    $arguments = array_merge([
        'httpVerb' => 'post',
        'webhookUrl' => 'https://example.com/webhooks',
        'payload' => ['event' => 'invoice.paid', 'data' => ['invoiceId' => 987]],
        'headers' => [],
        'meta' => ['event' => 'invoice.paid', 'subscription_id' => null, 'payload_id' => 'payload-uuid'],
        'tags' => [],
        'attempt' => 1,
        'response' => null,
        'errorType' => null,
        'errorMessage' => null,
        'uuid' => 'call-uuid',
        'transferStats' => null,
    ], $overrides);

    return new $eventClass(...$arguments);
}
