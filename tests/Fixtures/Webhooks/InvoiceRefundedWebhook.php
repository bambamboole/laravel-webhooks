<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Tests\Fixtures\Webhooks;

use Bambamboole\LaravelWebhooks\Attributes\WebhookEvent;

#[WebhookEvent(
    name: 'invoice.refunded',
    title: 'Invoice Refunded',
    summary: 'Sent when an invoice is refunded.',
    tags: ['billing', 'refunds'],
)]
final class InvoiceRefundedWebhook
{
    public function __construct(
        public int $invoiceId = 123,
        public int $refundId = 456,
    ) {}

    /**
     * @return array{invoiceId:int, refundId:int}
     */
    public function webhookPayload(): array
    {
        return [
            'invoiceId' => $this->invoiceId,
            'refundId' => $this->refundId,
        ];
    }
}
