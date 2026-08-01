<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Bambamboole\LaravelWebhooks\Models\WebhookDelivery;
use Spatie\WebhookServer\Events\WebhookCallEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

final class RecordWebhookDelivery
{
    public function handle(WebhookCallEvent $event): void
    {
        // Calls without our meta were dispatched through spatie directly,
        // not by this package — leave those alone.
        if (! isset($event->meta['event'])) {
            return;
        }

        WebhookDelivery::create([
            'subscription_id' => $event->meta['subscription_id'] ?? null,
            'call_uuid' => $event->uuid,
            'event' => $event->meta['event'],
            'url' => $event->webhookUrl,
            'http_verb' => $event->httpVerb,
            'payload' => $event->payload,
            'attempt' => $event->attempt,
            'status' => $event instanceof WebhookCallSucceededEvent ? WebhookDelivery::STATUS_SUCCEEDED : WebhookDelivery::STATUS_FAILED,
            'response_status' => $event->response?->getStatusCode(),
            'error_type' => $event->errorType,
            'error_message' => $event->errorMessage,
        ]);
    }
}
