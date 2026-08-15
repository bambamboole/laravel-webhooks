<?php

declare(strict_types=1);

use Bambamboole\LaravelWebhooks\Models\WebhookDelivery;
use Bambamboole\LaravelWebhooks\Models\WebhookSubscription as SubscriptionModel;
use Bambamboole\LaravelWebhooks\Tests\Fixtures\Models\HookedWebhookDelivery;
use Bambamboole\LaravelWebhooks\Tests\Fixtures\Models\ScopedWebhookSubscription;
use Bambamboole\LaravelWebhooks\Webhooks;
use Bambamboole\LaravelWebhooks\WebhookSubscriptionRepository;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

afterEach(function (): void {
    HookedWebhookDelivery::$createdEvents = [];
});

it('resolves the package models by default', function (): void {
    expect(Webhooks::subscriptionModel())->toBe(SubscriptionModel::class)
        ->and(Webhooks::deliveryModel())->toBe(WebhookDelivery::class);
});

it('records deliveries through the configured delivery model', function (): void {
    config()->set('webhooks.models.delivery', HookedWebhookDelivery::class);

    event(new WebhookCallSucceededEvent(
        httpVerb: 'post',
        webhookUrl: 'https://example.com/webhooks',
        payload: ['event' => 'invoice.paid'],
        headers: [],
        meta: ['event' => 'invoice.paid', 'subscription_id' => null, 'payload_id' => 'payload-uuid'],
        tags: [],
        attempt: 1,
        response: null,
        errorType: null,
        errorMessage: null,
        uuid: 'call-uuid',
        transferStats: null,
    ));

    expect(HookedWebhookDelivery::$createdEvents)->toBe(['invoice.paid'])
        ->and(WebhookDelivery::count())->toBe(1);
});

it('resolves the subscription relation to the configured subscription model', function (): void {
    config()->set('webhooks.models.subscription', ScopedWebhookSubscription::class);

    $subscription = SubscriptionModel::create([
        'name' => 'allowed',
        'url' => 'https://example.com/billing',
        'events' => ['invoice.paid'],
    ]);

    $delivery = WebhookDelivery::create([
        'subscription_id' => $subscription->id,
        'call_uuid' => 'call-uuid',
        'event' => 'invoice.paid',
        'url' => 'https://example.com/billing',
        'http_verb' => 'post',
        'payload' => ['event' => 'invoice.paid'],
        'attempt' => 1,
        'status' => WebhookDelivery::STATUS_SUCCEEDED,
    ]);

    expect($delivery->subscription)->toBeInstanceOf(ScopedWebhookSubscription::class);
});

it('applies the configured subscription model scopes when matching subscriptions', function (): void {
    config()->set('webhooks.models.subscription', ScopedWebhookSubscription::class);

    SubscriptionModel::create(['name' => 'allowed', 'url' => 'https://example.com/a', 'events' => ['invoice.paid']]);
    SubscriptionModel::create(['name' => 'blocked', 'url' => 'https://example.com/b', 'events' => ['invoice.paid']]);

    $subscriptions = collect(app(WebhookSubscriptionRepository::class)->forEvent('invoice.paid', new stdClass));

    expect($subscriptions)->toHaveCount(1)
        ->and($subscriptions->sole()->url)->toBe('https://example.com/a');
});

it('rejects configured model classes that do not extend the package models', function (): void {
    config()->set('webhooks.models.delivery', stdClass::class);

    expect(fn (): string => Webhooks::deliveryModel())
        ->toThrow(RuntimeException::class, 'The [webhooks.models.delivery] config must be a class extending');
});
