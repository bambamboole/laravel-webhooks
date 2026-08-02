<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Illuminate\Support\Str;
use RuntimeException;

final readonly class WebhookPayloadFactory
{
    /**
     * @return array{id: string, event: string, createdAt: string, links?: array<string, string>, data: array<mixed>}
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

        return $this->envelope($definition->name, $data, $this->links($event));
    }

    /**
     * @param  array<mixed>  $data
     * @param  array<string, string>  $links
     * @return array{id: string, event: string, createdAt: string, links?: array<string, string>, data: array<mixed>}
     */
    public function envelope(string $eventName, array $data, array $links = []): array
    {
        $envelope = [
            'id' => (string) Str::uuid(),
            'event' => $eventName,
            'createdAt' => now()->toISOString(),
        ];

        if ($links !== []) {
            $envelope['links'] = $links;
        }

        $envelope['data'] = $data;

        return $envelope;
    }

    /**
     * @return array<string, string>
     */
    private function links(object $event): array
    {
        if (! method_exists($event, 'webhookLinks')) {
            return [];
        }

        $eventClass = $event::class;
        $links = $event->webhookLinks();

        if (! is_array($links)) {
            throw new RuntimeException("Webhook links method [webhookLinks] on [{$eventClass}] must return an array");
        }

        return $links;
    }
}
