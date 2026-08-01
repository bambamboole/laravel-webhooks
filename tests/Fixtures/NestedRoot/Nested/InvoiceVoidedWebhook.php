<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Tests\Fixtures\NestedRoot\Nested;

use Bambamboole\LaravelWebhooks\Attributes\WebhookEvent;

#[WebhookEvent(name: 'invoice.voided', title: 'Invoice Voided')]
final class InvoiceVoidedWebhook
{
    /**
     * @return array{invoiceId:int}
     */
    public function webhookPayload(): array
    {
        return [
            'invoiceId' => 789,
        ];
    }
}
