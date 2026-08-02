<?php

declare(strict_types=1);

use Bambamboole\LaravelWebhooks\DatabaseWebhookSubscriptionRepository;
use Bambamboole\LaravelWebhooks\DispatchWebhookEvent;
use Bambamboole\LaravelWebhooks\Models\WebhookSubscription as SubscriptionModel;
use Bambamboole\LaravelWebhooks\Tests\Fixtures\Webhooks\InvoicePaidWebhook;
use Bambamboole\LaravelWebhooks\WebhookEventRegistry;
use Bambamboole\LaravelWebhooks\WebhookSubscription;
use Bambamboole\LaravelWebhooks\WebhookSubscriptionRepository;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\WebhookServer\CallWebhookJob;

it('is bound as the default subscription repository', function (): void {
    expect(app(WebhookSubscriptionRepository::class)::class)
        ->toBe(DatabaseWebhookSubscriptionRepository::class);
});

it('returns active subscriptions matching the event or wildcard', function (): void {
    $matching = SubscriptionModel::create([
        'name' => 'Billing system',
        'url' => 'https://example.com/billing',
        'secret' => 'signing-secret',
        'headers' => ['X-Tenant' => 'acme'],
        'events' => ['invoice.paid', 'invoice.refunded'],
    ]);
    $wildcard = SubscriptionModel::create([
        'url' => 'https://example.com/firehose',
        'events' => ['*'],
    ]);
    SubscriptionModel::create([
        'url' => 'https://example.com/inactive',
        'events' => ['invoice.paid'],
        'active' => false,
    ]);
    SubscriptionModel::create([
        'url' => 'https://example.com/other',
        'events' => ['invoice.refunded'],
    ]);

    $subscriptions = collect(app(WebhookSubscriptionRepository::class)->forEvent('invoice.paid', new InvoicePaidWebhook))
        ->keyBy(fn (WebhookSubscription $subscription): string => $subscription->url);

    expect($matching->id)->toBeUuid()
        ->and($subscriptions)->toHaveCount(2)
        ->and($subscriptions['https://example.com/billing']->secret)->toBe('signing-secret')
        ->and($subscriptions['https://example.com/billing']->headers)->toBe(['X-Tenant' => 'acme'])
        ->and($subscriptions['https://example.com/billing']->id)->toBe($matching->id)
        ->and($subscriptions['https://example.com/firehose']->secret)->toBeNull()
        ->and($subscriptions['https://example.com/firehose']->id)->toBe($wildcard->id);
});

it('matches prefix wildcard event patterns', function (): void {
    SubscriptionModel::create([
        'url' => 'https://example.com/invoices',
        'events' => ['invoice.*'],
    ]);
    SubscriptionModel::create([
        'url' => 'https://example.com/payments',
        'events' => ['payment.*'],
    ]);

    $subscriptions = collect(app(WebhookSubscriptionRepository::class)->forEvent('invoice.paid', new InvoicePaidWebhook))
        ->map(fn (WebhookSubscription $subscription): string => $subscription->url);

    expect($subscriptions->all())->toBe(['https://example.com/invoices']);
});

it('encrypts subscription secrets at rest', function (): void {
    $subscription = SubscriptionModel::create([
        'url' => 'https://example.com/billing',
        'secret' => 'signing-secret',
        'events' => ['invoice.paid'],
    ]);

    $raw = DB::table('webhook_subscriptions')->where('id', $subscription->id)->value('secret');

    expect($raw)->not->toBe('signing-secret')
        ->and($subscription->fresh()?->secret)->toBe('signing-secret');
});

it('pings a subscription with a signed ping envelope', function (): void {
    Bus::fake();

    $subscription = SubscriptionModel::create([
        'url' => 'https://example.com/billing',
        'secret' => 'signing-secret',
        'headers' => ['X-Tenant' => 'acme'],
        'events' => ['invoice.paid'],
    ]);

    $subscription->ping();

    Bus::assertDispatched(CallWebhookJob::class, function (CallWebhookJob $job) use ($subscription): bool {
        expect($job->webhookUrl)->toBe('https://example.com/billing')
            ->and($job->payload['event'])->toBe('ping')
            ->and($job->payload['data'])->toBe([])
            ->and(Str::isUuid($job->payload['id']))->toBeTrue()
            ->and($job->headers)->toMatchArray(['X-Tenant' => 'acme'])
            ->and($job->headers)->toHaveKey('Signature')
            ->and($job->meta)->toMatchArray([
                'event' => 'ping',
                'subscription_id' => $subscription->id,
                'payload_id' => $job->payload['id'],
            ]);

        return true;
    });
});

it('delivers database subscriptions end to end', function (): void {
    Bus::fake();
    config()->set('webhooks.scan_paths', [dirname(__DIR__).'/Fixtures/Webhooks']);
    app()->forgetInstance(WebhookEventRegistry::class);

    SubscriptionModel::create([
        'url' => 'https://example.com/billing',
        'secret' => 'signing-secret',
        'headers' => ['X-Tenant' => 'acme'],
        'events' => ['invoice.paid'],
    ]);

    app(DispatchWebhookEvent::class)->handle(new InvoicePaidWebhook(invoiceId: 987, amount: 6500));

    Bus::assertDispatched(CallWebhookJob::class, function (CallWebhookJob $job): bool {
        expect($job->webhookUrl)->toBe('https://example.com/billing')
            ->and($job->payload)->toMatchArray([
                'event' => 'invoice.paid',
                'data' => [
                    'invoiceId' => 987,
                    'amount' => 6500,
                ],
            ])
            ->and($job->headers)->toMatchArray(['X-Tenant' => 'acme'])
            ->and($job->headers)->toHaveKey('Signature');

        return true;
    });
});
