<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Models;

use Bambamboole\LaravelWebhooks\DispatchWebhookEvent;
use Bambamboole\LaravelWebhooks\WebhookPayloadFactory;
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

    /**
     * Subscribed event entries match by exact name, a dot-boundary prefix
     * wildcard (`invoice.*`), or the `*` catch-all.
     */
    public function matchesEvent(string $eventName): bool
    {
        return array_intersect($this->events, self::candidatePatterns($eventName)) !== [];
    }

    /**
     * Every pattern that can match the given event name. Matching is
     * expressed as containment so it stays a portable database query.
     *
     * @return list<string>
     */
    public static function candidatePatterns(string $eventName): array
    {
        $patterns = [$eventName, '*'];
        $segments = explode('.', $eventName);
        array_pop($segments);

        while ($segments !== []) {
            $patterns[] = implode('.', $segments).'.*';
            array_pop($segments);
        }

        return $patterns;
    }

    /**
     * Send a signed ping envelope to verify the endpoint. The ping is
     * delivered and logged like any webhook call.
     */
    public function ping(): void
    {
        app(DispatchWebhookEvent::class)->send(
            $this->toSubscription(),
            'ping',
            app(WebhookPayloadFactory::class)->envelope('ping', []),
        );
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
