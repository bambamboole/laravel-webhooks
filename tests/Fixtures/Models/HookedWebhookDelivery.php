<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Tests\Fixtures\Models;

use Bambamboole\LaravelWebhooks\Models\WebhookDelivery;

class HookedWebhookDelivery extends WebhookDelivery
{
    /** @var list<string> */
    public static array $createdEvents = [];

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $delivery): void {
            self::$createdEvents[] = $delivery->event;
        });
    }
}
