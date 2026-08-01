<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Illuminate\Support\Str;
use ReflectionClass;
use RuntimeException;

final readonly class WebhookPayloadFactory
{
    /**
     * @return array{id: string, event: string, createdAt: string, data: array<mixed>}
     */
    public function make(WebhookEventDefinition $definition, object $event): array
    {
        $payloadMethod = 'webhookPayload';
        $eventClass = $event::class;
        $reflection = new ReflectionClass($event);

        if (! $reflection->hasMethod($payloadMethod)) {
            throw new RuntimeException("Webhook payload method [{$payloadMethod}] is missing on [{$eventClass}]");
        }

        $method = $reflection->getMethod($payloadMethod);

        if (! $method->isPublic()) {
            throw new RuntimeException("Webhook payload method [{$payloadMethod}] on [{$eventClass}] must be public");
        }

        if ($method->getNumberOfRequiredParameters() > 0) {
            throw new RuntimeException("Webhook payload method [{$payloadMethod}] on [{$eventClass}] must have zero required parameters");
        }

        $data = $method->invoke($event);

        if (! is_array($data)) {
            throw new RuntimeException("Webhook payload method [{$payloadMethod}] on [{$eventClass}] must return an array");
        }

        return [
            'id' => (string) Str::uuid(),
            'event' => $definition->name,
            'createdAt' => now()->toISOString(),
            'data' => $data,
        ];
    }
}
