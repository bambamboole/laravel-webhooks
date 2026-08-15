<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Tests\Fixtures\Models;

use Bambamboole\LaravelWebhooks\Models\WebhookSubscription;
use Illuminate\Database\Eloquent\Builder;

class ScopedWebhookSubscription extends WebhookSubscription
{
    #[\Override]
    protected static function booted(): void
    {
        static::addGlobalScope('named-allowed', function (Builder $query): void {
            $query->where('name', 'allowed');
        });
    }
}
