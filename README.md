# Laravel Webhooks

Attribute-driven outgoing webhooks for Laravel: annotate event classes, manage subscriptions in the database, and
deliver signed payloads through [spatie/laravel-webhook-server](https://github.com/spatie/laravel-webhook-server).

## Installation

```bash
composer require bambamboole/laravel-webhooks
```

Delivery goes through [spatie/laravel-webhook-server](https://github.com/spatie/laravel-webhook-server), which is
installed as a dependency. The `webhook_subscriptions` and `webhook_deliveries` migrations run automatically. Publish
the config if you need to change defaults:

```bash
php artisan vendor:publish --tag=laravel-webhooks-config
```

## Defining webhook events

Annotate any class with `#[WebhookEvent]` and give it a payload method. By default the package scans `app/Events`;
adjust `webhooks.scan_paths` in the config to change that.

```php
use Bambamboole\LaravelWebhooks\Attributes\WebhookEvent;

#[WebhookEvent(
    name: 'invoice.paid',
    title: 'Invoice paid',
    summary: 'Sent when an invoice is paid.',
    tags: ['billing'],
)]
final class InvoicePaid
{
    public function __construct(public int $invoiceId, public int $amount) {}

    /**
     * @return array{invoiceId:int, amount:int}
     */
    public function webhookPayload(): array
    {
        return [
            'invoiceId' => $this->invoiceId,
            'amount' => $this->amount,
        ];
    }

    /**
     * @return array{self:string}
     */
    public function webhookLinks(): array
    {
        return [
            'self' => route('api.invoices.show', $this->invoiceId),
        ];
    }
}
```

Every delivery wraps the payload in a stable envelope. An optional `webhookLinks()` method adds JSON:API-style
resource links so receivers can navigate back to (or re-fetch) the resources an event is about; the `links` key is
omitted when an event defines none. Links live in the signed body, so receivers can trust them after verifying the
signature:

```json
{
    "id": "9d3c…",
    "event": "invoice.paid",
    "createdAt": "2026-07-03T12:34:56.000000Z",
    "links": {
        "self": "https://api.example.com/invoices/987"
    },
    "data": {
        "invoiceId": 987,
        "amount": 6500
    }
}
```

`WebhookEventRegistry::all()` returns the discovered definitions, which an app can use to power its own webhook
setup UI:

```php
use Bambamboole\LaravelWebhooks\WebhookEventRegistry;

$events = collect(app(WebhookEventRegistry::class)->all())
    ->map(fn ($event) => [
        'name' => $event->name,
        'title' => $event->title,
        'summary' => $event->summary,
    ]);
```

## Managing subscriptions

Subscriptions live in the `webhook_subscriptions` table via the shipped Eloquent model. `events` holds wildcard
patterns: `invoice.paid` matches exactly, `invoice.*` matches every invoice event, and `'*'` subscribes to everything.
Secrets are encrypted at rest and used to sign deliveries.

```php
use Bambamboole\LaravelWebhooks\Models\WebhookSubscription;

WebhookSubscription::create([
    'name' => 'Billing system',
    'url' => 'https://example.com/webhooks',
    'secret' => 'signing-secret',
    'headers' => ['X-Tenant' => 'acme'],
    'events' => ['invoice.paid', 'invoice.refunded'],
]);
```

Verify an endpoint by sending a signed `ping` event through the normal delivery pipeline:

```php
$subscription->ping();
```

To source subscriptions from somewhere else, bind your own repository — the database repository is only a default
(`bindIf`):

```php
use Bambamboole\LaravelWebhooks\WebhookSubscription;
use Bambamboole\LaravelWebhooks\WebhookSubscriptionRepository;

final class CustomWebhookSubscriptionRepository implements WebhookSubscriptionRepository
{
    public function forEvent(string $eventName, object $event): iterable
    {
        yield new WebhookSubscription(
            url: 'https://example.com/webhooks',
            secret: 'signing-secret',
            headers: ['X-Webhook-Source' => 'app'],
            id: 'subscription-123',
        );
    }
}
```

## Dispatching

Webhook event classes are regular Laravel events — fire them and the package delivers them. Listeners for every
discovered `#[WebhookEvent]` class are registered automatically:

```php
event(new InvoicePaid(invoiceId: 987, amount: 6500));
```

Discovery scans the configured paths at every boot. Cache the result in production like you cache routes:

```bash
php artisan webhooks:cache    # writes bootstrap/cache/webhooks.php, loaded instead of scanning
php artisan webhooks:clear    # removes the cache
php artisan webhooks:events   # lists what discovery found
```

Alternatively set `webhooks.auto_listen` to `false` and wire the dispatcher yourself:

```php
use Bambamboole\LaravelWebhooks\DispatchWebhookEvent;
use Illuminate\Support\Facades\Event;

Event::listen(InvoicePaid::class, DispatchWebhookEvent::class);
```

Each active subscription for the event receives one signed call through spatie's queue-backed webhook server. Calls
are signed with the subscription secret (unsigned when no secret is set) and include a timestamp against replay
attacks (`webhooks.dispatcher.use_timestamp`).

## Delivery log

Every call attempt is recorded in the `webhook_deliveries` table (UUIDv7 keys): event name, url, payload, attempt,
status, HTTP response status, and error details. Retries of the same call share a `call_uuid`. The status is
`succeeded`, `failed` (a retry follows), or `final_failed` (retries exhausted).

```php
use Bambamboole\LaravelWebhooks\Models\WebhookDelivery;

WebhookDelivery::where('status', WebhookDelivery::STATUS_FAILED)->latest()->get();

$delivery->subscription; // the WebhookSubscription it was delivered to, if any
```

Resend a delivery — the stored payload goes out again using the subscription's *current* url, secret, and headers,
so rotated secrets and moved endpoints are honored:

```php
$delivery->resend();
```

Calls dispatched through spatie's webhook server directly (without this package's dispatcher) are not recorded.

The log is prunable: rows older than `webhooks.deliveries.prune_after_days` (default 30, `null` keeps them forever)
are removed when your app schedules pruning:

```php
Schedule::command('model:prune', ['--model' => [WebhookDelivery::class]])->daily();
```

## Development

```bash
composer test    # pest
composer check   # pint + phpstan + rector + pest
composer serve   # workbench app
```

## License

[MIT](LICENSE.md)
