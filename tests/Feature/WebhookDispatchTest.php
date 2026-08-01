<?php

declare(strict_types=1);

use Bambamboole\LaravelWebhooks\DispatchWebhookEvent;
use Bambamboole\LaravelWebhooks\Tests\Fixtures\Webhooks\InvoicePaidWebhook;
use Bambamboole\LaravelWebhooks\WebhookEventDefinition;
use Bambamboole\LaravelWebhooks\WebhookEventRegistry;
use Bambamboole\LaravelWebhooks\WebhookPayloadFactory;
use Bambamboole\LaravelWebhooks\WebhookSubscription;
use Bambamboole\LaravelWebhooks\WebhookSubscriptionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Spatie\WebhookServer\CallWebhookJob;

afterEach(function (): void {
    Carbon::setTestNow();
});

it('builds a stable envelope for an invoice paid webhook', function (): void {
    Carbon::setTestNow('2026-07-03 12:34:56');

    $payload = app(WebhookPayloadFactory::class)->make(
        invoicePaidWebhookDefinition(),
        new InvoicePaidWebhook(invoiceId: 987, amount: 6500),
    );

    expect(Str::isUuid($payload['id']))->toBeTrue()
        ->and($payload)->toEqual([
            'id' => $payload['id'],
            'event' => 'invoice.paid',
            'createdAt' => '2026-07-03T12:34:56.000000Z',
            'data' => [
                'invoiceId' => 987,
                'amount' => 6500,
            ],
        ]);
});

it('dispatches documented webhook events through spatie', function (): void {
    Bus::fake();
    config()->set('webhooks.scan_paths', [dirname(__DIR__).'/Fixtures/Webhooks']);
    app()->forgetInstance(WebhookEventRegistry::class);

    app()->bind(WebhookSubscriptionRepository::class, RecordingWebhookSubscriptionRepository::class);

    $event = new InvoicePaidWebhook(invoiceId: 987, amount: 6500);

    app(DispatchWebhookEvent::class)->handle($event);

    Bus::assertDispatched(CallWebhookJob::class, function (CallWebhookJob $job) use ($event): bool {
        expect($job->webhookUrl)->toBe('https://example.com/webhooks')
            ->and($job->payload)->toMatchArray([
                'event' => 'invoice.paid',
                'data' => [
                    'invoiceId' => 987,
                    'amount' => 6500,
                ],
            ])
            ->and($job->headers)->toMatchArray([
                'X-Webhook-Source' => 'laravel-webhooks',
            ])
            ->and($job->headers)->not->toHaveKey('Signature')
            ->and($job->meta)->toMatchArray([
                'event' => 'invoice.paid',
                'subscription_id' => 'subscription-123',
                'payload_id' => $job->payload['id'],
            ])
            ->and($job->useTimestamp)->toBeTrue();

        expect(RecordingWebhookSubscriptionRepository::$eventName)->toBe('invoice.paid')
            ->and(RecordingWebhookSubscriptionRepository::$event)->toBe($event);

        return true;
    });
});

it('ignores events that are not documented webhooks', function (): void {
    Bus::fake();
    config()->set('webhooks.scan_paths', [dirname(__DIR__).'/Fixtures/Webhooks']);
    app()->forgetInstance(WebhookEventRegistry::class);

    app(DispatchWebhookEvent::class)->handle(new UndocumentedWebhookEvent);

    Bus::assertNothingDispatched();
});

it('throws a useful runtime exception when a repository yields invalid subscriptions', function (): void {
    Bus::fake();
    config()->set('webhooks.scan_paths', [dirname(__DIR__).'/Fixtures/Webhooks']);
    app()->forgetInstance(WebhookEventRegistry::class);

    app()->bind(WebhookSubscriptionRepository::class, InvalidWebhookSubscriptionRepository::class);

    expect(fn () => app(DispatchWebhookEvent::class)->handle(new InvoicePaidWebhook))
        ->toThrow(
            RuntimeException::class,
            'Webhook subscription repositories must yield [Bambamboole\LaravelWebhooks\WebhookSubscription] instances.',
        );

    Bus::assertNothingDispatched();
});

it('throws a useful runtime exception when the payload method is missing', function (): void {
    $definition = webhookDefinitionFor('invoice.missing', MissingPayloadMethodWebhook::class);

    expect(fn () => app(WebhookPayloadFactory::class)->make($definition, new MissingPayloadMethodWebhook))
        ->toThrow(RuntimeException::class, 'Webhook payload method [webhookPayload] is missing on [MissingPayloadMethodWebhook]');
});

it('throws a useful runtime exception when the payload method is handled by magic call', function (): void {
    $definition = webhookDefinitionFor('invoice.magic', MagicPayloadMethodWebhook::class);

    expect(fn () => app(WebhookPayloadFactory::class)->make($definition, new MagicPayloadMethodWebhook))
        ->toThrow(RuntimeException::class, 'Webhook payload method [webhookPayload] is missing on [MagicPayloadMethodWebhook]');
});

it('fails with a native error when the payload method is not public', function (): void {
    $definition = webhookDefinitionFor('invoice.private', PrivatePayloadMethodWebhook::class);

    expect(fn () => app(WebhookPayloadFactory::class)->make($definition, new PrivatePayloadMethodWebhook))
        ->toThrow(Error::class);
});

it('fails with a native error when the payload method requires parameters', function (): void {
    $definition = webhookDefinitionFor('invoice.parameters', RequiredParameterPayloadWebhook::class);

    expect(fn () => app(WebhookPayloadFactory::class)->make($definition, new RequiredParameterPayloadWebhook))
        ->toThrow(ArgumentCountError::class);
});

it('throws a useful runtime exception when the payload method does not return an array', function (): void {
    $definition = webhookDefinitionFor('invoice.invalid', NonArrayPayloadWebhook::class);

    expect(fn () => app(WebhookPayloadFactory::class)->make($definition, new NonArrayPayloadWebhook))
        ->toThrow(RuntimeException::class, 'Webhook payload method [webhookPayload] on [NonArrayPayloadWebhook] must return an array');
});

function invoicePaidWebhookDefinition(): WebhookEventDefinition
{
    return webhookDefinitionFor('invoice.paid', InvoicePaidWebhook::class);
}

/**
 * @param  class-string  $class
 */
function webhookDefinitionFor(string $name, string $class): WebhookEventDefinition
{
    return new WebhookEventDefinition(
        name: $name,
        class: $class,
        title: null,
        summary: null,
        description: null,
        tags: [],
    );
}

final class MissingPayloadMethodWebhook {}

final class MagicPayloadMethodWebhook
{
    /**
     * @param  array<int, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function __call(string $method, array $arguments): array
    {
        return ['magic' => $method];
    }
}

final class PrivatePayloadMethodWebhook
{
    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->webhookPayload();
    }

    /**
     * @return array<string, mixed>
     */
    private function webhookPayload(): array
    {
        return [];
    }
}

final class RequiredParameterPayloadWebhook
{
    /**
     * @return array<string, mixed>
     */
    public function webhookPayload(string $scope): array
    {
        return ['scope' => $scope];
    }
}

final class NonArrayPayloadWebhook
{
    public function webhookPayload(): string
    {
        return 'invalid';
    }
}

final class RecordingWebhookSubscriptionRepository implements WebhookSubscriptionRepository
{
    public static ?string $eventName = null;

    public static ?object $event = null;

    public function forEvent(string $eventName, object $event): iterable
    {
        self::$eventName = $eventName;
        self::$event = $event;

        return [
            new WebhookSubscription(
                url: 'https://example.com/webhooks',
                headers: ['X-Webhook-Source' => 'laravel-webhooks'],
                id: 'subscription-123',
            ),
        ];
    }
}

final class InvalidWebhookSubscriptionRepository implements WebhookSubscriptionRepository
{
    public function forEvent(string $eventName, object $event): iterable
    {
        return [invalidWebhookSubscription()];
    }
}

function invalidWebhookSubscription(): mixed
{
    return 'invalid';
}

final class UndocumentedWebhookEvent {}
