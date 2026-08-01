<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Tests\Fixtures\Webhooks;

use Bambamboole\LaravelWebhooks\Attributes\WebhookEvent;

#[WebhookEvent(
    name: 'invoice.paid',
    title: 'Invoice Paid',
    summary: 'Sent when an invoice is paid.',
    description: 'Customers can subscribe to this webhook to react to paid invoices.',
    tags: ['billing'],
)]
final class InvoicePaidWebhook
{
    public function __construct(
        public int $invoiceId = 123,
        public int $amount = 4999,
    ) {}

    /**
     * @return array{invoiceId:int, amount:int}
     */
    public function webhookPayload(): array
    {
        return [
            'invoiceId' => $this->invoiceId,
            'amount' => $this->amount,
        ];
    }
}
