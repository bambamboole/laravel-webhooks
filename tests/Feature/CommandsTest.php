<?php

declare(strict_types=1);

use Bambamboole\LaravelWebhooks\Support\ClassDiscoverer;
use Bambamboole\LaravelWebhooks\WebhookEventRegistry;
use Workbench\App\Events\InvoicePaid;

afterEach(function (): void {
    $path = app()->bootstrapPath('cache/webhooks.php');

    if (is_file($path)) {
        unlink($path);
    }
});

it('caches discovered webhook events for the registry to load', function (): void {
    config()->set('webhooks.scan_paths', [dirname(__DIR__).'/Fixtures/Webhooks']);

    $this->artisan('webhooks:cache')->assertSuccessful();

    expect(is_file(app()->bootstrapPath('cache/webhooks.php')))->toBeTrue();

    config()->set('webhooks.scan_paths', []);

    $definitions = new WebhookEventRegistry(new ClassDiscoverer)->all();

    expect($definitions)->toHaveCount(2)
        ->and($definitions[0]->name)->toBe('invoice.paid')
        ->and($definitions[0]->title)->toBe('Invoice Paid')
        ->and($definitions[1]->name)->toBe('invoice.refunded');
});

it('lists the discovered webhook events', function (): void {
    $this->artisan('webhooks:events')
        ->expectsTable(
            ['Event', 'Title', 'Class'],
            [['invoice.paid', 'Invoice Paid', InvoicePaid::class]],
        )
        ->assertSuccessful();
});

it('reports when no webhook events are discovered', function (): void {
    config()->set('webhooks.scan_paths', []);
    app()->forgetInstance(WebhookEventRegistry::class);

    $this->artisan('webhooks:events')
        ->expectsOutputToContain('No webhook events discovered.')
        ->assertSuccessful();
});

it('clears the webhook events cache', function (): void {
    $this->artisan('webhooks:cache')->assertSuccessful();

    expect(is_file(app()->bootstrapPath('cache/webhooks.php')))->toBeTrue();

    $this->artisan('webhooks:clear')->assertSuccessful();

    expect(is_file(app()->bootstrapPath('cache/webhooks.php')))->toBeFalse();
});
