<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Bambamboole\LaravelWebhooks\Models\WebhookDelivery;
use Bambamboole\LaravelWebhooks\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Resolves the Eloquent model classes the package works with. Apps swap in
 * their own subclasses (e.g. to add tenancy or global scopes) via the
 * `webhooks.models` config; every internal read and write goes through here.
 */
final class Webhooks
{
    /**
     * @return class-string<WebhookSubscription>
     */
    public static function subscriptionModel(): string
    {
        return self::model('subscription', WebhookSubscription::class);
    }

    /**
     * @return class-string<WebhookDelivery>
     */
    public static function deliveryModel(): string
    {
        return self::model('delivery', WebhookDelivery::class);
    }

    /**
     * @return Builder<WebhookSubscription>
     */
    public static function subscriptionQuery(): Builder
    {
        return self::subscriptionModel()::query();
    }

    /**
     * @return Builder<WebhookDelivery>
     */
    public static function deliveryQuery(): Builder
    {
        return self::deliveryModel()::query();
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $base
     * @return class-string<TModel>
     */
    private static function model(string $key, string $base): string
    {
        $configured = config("webhooks.models.{$key}", $base);

        if (! is_string($configured) || ! is_a($configured, $base, true)) {
            throw new RuntimeException(
                "The [webhooks.models.{$key}] config must be a class extending [{$base}].",
            );
        }

        return $configured;
    }
}
