<?php

declare(strict_types=1);

use Bambamboole\LaravelWebhooks\Models\WebhookDelivery;
use Bambamboole\LaravelWebhooks\Models\WebhookSubscription as SubscriptionModel;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use Spatie\WebhookServer\Events\WebhookCallEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

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

it('ignores spatie webhook calls not dispatched by this package', function (): void {
    event(webhookCallEvent(WebhookCallSucceededEvent::class, [
        'meta' => ['origin' => 'somewhere-else'],
    ]));

    expect(WebhookDelivery::count())->toBe(0);
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
