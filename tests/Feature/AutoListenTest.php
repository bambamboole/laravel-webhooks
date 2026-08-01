<?php

declare(strict_types=1);

use Bambamboole\LaravelWebhooks\Models\WebhookSubscription as SubscriptionModel;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookServer\CallWebhookJob;
use Workbench\App\Events\InvoicePaid;

it('auto-registers listeners for discovered webhook events', function (): void {
    expect(Event::hasListeners(InvoicePaid::class))->toBeTrue();
});

it('delivers auto-listened events end to end', function (): void {
    Bus::fake();

    SubscriptionModel::create([
        'url' => 'https://example.com/billing',
        'events' => ['invoice.paid'],
    ]);

    event(new InvoicePaid(invoiceId: 7, amount: 100));

    Bus::assertDispatched(CallWebhookJob::class, function (CallWebhookJob $job): bool {
        expect($job->webhookUrl)->toBe('https://example.com/billing')
            ->and($job->payload)->toMatchArray([
                'event' => 'invoice.paid',
                'data' => ['invoiceId' => 7, 'amount' => 100],
            ]);

        return true;
    });
});
