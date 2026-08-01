<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Models;

use Bambamboole\LaravelWebhooks\WebhookSubscription as Subscription;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $name
 * @property string $url
 * @property string|null $secret
 * @property array<string, string> $headers
 * @property list<string> $events
 * @property bool $active
 */
class WebhookSubscription extends Model
{
    use HasUuids;

    protected $table = 'webhook_subscriptions';

    protected $guarded = [];

    protected $attributes = [
        'headers' => '{}',
        'active' => true,
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'events' => 'array',
            'active' => 'boolean',
            'secret' => 'encrypted',
        ];
    }

    public function toSubscription(): Subscription
    {
        return new Subscription(
            url: $this->url,
            secret: $this->secret,
            headers: $this->headers,
            id: (string) $this->getKey(),
        );
    }
}
