<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use RuntimeException;
use Spatie\WebhookServer\WebhookCall;

final readonly class DispatchWebhookEvent
{
    public function __construct(
        private WebhookEventRegistry $events,
        private WebhookPayloadFactory $payloads,
        private WebhookSubscriptionRepository $subscriptions,
    ) {}

    public function handle(object $event): void
    {
        $definition = $this->events->forClass($event::class);

        if (! $definition instanceof WebhookEventDefinition) {
            return;
        }

        $payload = $this->payloads->make($definition, $event);

        foreach ($this->subscriptionsFor($definition->name, $event) as $subscription) {
            if (! $subscription instanceof WebhookSubscription) {
                throw new RuntimeException(
                    'Webhook subscription repositories must yield [Bambamboole\LaravelWebhooks\WebhookSubscription] instances.',
                );
            }

            $call = WebhookCall::create()
                ->url($subscription->url)
                ->payload($payload->body)
                ->withHeaders($subscription->headers)
                ->meta([
                    'event' => $definition->name,
                    'subscription_id' => $subscription->id,
                    'payload_id' => $payload->id,
                ]);

            if ($subscription->secret !== null) {
                $call->useSecret($subscription->secret);
            } else {
                $call->doNotSign();
            }

            if (config('webhooks.dispatcher.use_timestamp', true)) {
                $call->useTimestamp();
            }

            $call->dispatch();
        }
    }

    /**
     * App repositories may yield anything at runtime despite the interface
     * generics, so widen to mixed to keep the instanceof guard meaningful.
     *
     * @return iterable<mixed>
     */
    private function subscriptionsFor(string $eventName, object $event): iterable
    {
        return $this->subscriptions->forEvent($eventName, $event);
    }
}
