<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

interface WebhookSubscriptionRepository
{
    /**
     * @return iterable<WebhookSubscription>
     */
    public function forEvent(string $eventName, object $event): iterable;
}
