<?php

declare(strict_types=1);

use Bambamboole\LaravelWebhooks\DatabaseWebhookSubscriptionRepository;
use Bambamboole\LaravelWebhooks\DispatchWebhookEvent;
use Bambamboole\LaravelWebhooks\Models\WebhookSubscription as SubscriptionModel;
use Bambamboole\LaravelWebhooks\Tests\Fixtures\Webhooks\InvoicePaidWebhook;
use Bambamboole\LaravelWebhooks\WebhookSubscriptionRepository;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
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

    $subscriptions = iterator_to_array(
        app(WebhookSubscriptionRepository::class)->forEvent('invoice.paid', new InvoicePaidWebhook),
    );

    expect($subscriptions)->toHaveCount(2)
        ->and($subscriptions[0]->url)->toBe('https://example.com/billing')
        ->and($subscriptions[0]->secret)->toBe('signing-secret')
        ->and($subscriptions[0]->headers)->toBe(['X-Tenant' => 'acme'])
        ->and($subscriptions[0]->id)->toBe((string) $matching->id)
        ->and($subscriptions[1]->url)->toBe('https://example.com/firehose')
        ->and($subscriptions[1]->secret)->toBeNull()
        ->and($subscriptions[1]->id)->toBe((string) $wildcard->id);
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

it('delivers database subscriptions end to end', function (): void {
    Bus::fake();
    config()->set('webhooks.scan_paths', [dirname(__DIR__).'/Fixtures/Webhooks']);

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
