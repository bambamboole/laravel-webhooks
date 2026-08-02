<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Bambamboole\LaravelWebhooks\Models\WebhookSubscription as SubscriptionModel;
use Illuminate\Database\Eloquent\Builder;

final readonly class DatabaseWebhookSubscriptionRepository implements WebhookSubscriptionRepository
{
    public function forEvent(string $eventName, object $event): iterable
    {
        return SubscriptionModel::query()
            ->where('active', true)
            ->where(function (Builder $query) use ($eventName): void {
                foreach (SubscriptionModel::candidatePatterns($eventName) as $pattern) {
                    $query->orWhereJsonContains('events', $pattern);
                }
            })
            ->get()
            ->map(fn (SubscriptionModel $subscription): WebhookSubscription => $subscription->toSubscription());
    }
}
