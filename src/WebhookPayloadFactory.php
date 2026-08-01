<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Illuminate\Support\Str;
use RuntimeException;

final readonly class WebhookPayloadFactory
{
    /**
     * @return array{id: string, event: string, createdAt: string, data: array<mixed>}
     */
    public function make(WebhookEventDefinition $definition, object $event): array
    {
        $eventClass = $event::class;

        if (! method_exists($event, 'webhookPayload')) {
            throw new RuntimeException("Webhook payload method [webhookPayload] is missing on [{$eventClass}]");
        }

        $data = $event->webhookPayload();

        if (! is_array($data)) {
            throw new RuntimeException("Webhook payload method [webhookPayload] on [{$eventClass}] must return an array");
        }

        return [
            'id' => (string) Str::uuid(),
            'event' => $definition->name,
            'createdAt' => now()->toISOString(),
            'data' => $data,
        ];
    }
}
