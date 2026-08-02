<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Bambamboole\LaravelWebhooks\Models\WebhookSubscription as SubscriptionModel;

final readonly class DatabaseWebhookSubscriptionRepository implements WebhookSubscriptionRepository
{
    public function forEvent(string $eventName, object $event): iterable
    {
        // ponytail: loads every active subscription and matches in PHP so
        // wildcard patterns work across drivers; push matching into SQL if
        // subscription counts get large.
        return SubscriptionModel::query()
            ->where('active', true)
            ->get()
            ->filter(fn (SubscriptionModel $subscription): bool => $subscription->matchesEvent($eventName))
            ->map(fn (SubscriptionModel $subscription): WebhookSubscription => $subscription->toSubscription())
            ->values();
    }
}
