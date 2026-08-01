<?php

declare(strict_types=1);

use Bambamboole\LaravelWebhooks\Tests\Fixtures\NestedRoot\Nested\InvoiceVoidedWebhook;
use Bambamboole\LaravelWebhooks\Tests\Fixtures\Webhooks\InvoicePaidWebhook;
use Bambamboole\LaravelWebhooks\Tests\Fixtures\Webhooks\InvoiceRefundedWebhook;
use Bambamboole\LaravelWebhooks\WebhookEventDefinition;
use Bambamboole\LaravelWebhooks\WebhookEventRegistry;

it('discovers webhook event definitions sorted by event name', function (): void {
    config()->set('webhooks.scan_paths', [webhookFixturePath('Webhooks')]);

    $definitions = app(WebhookEventRegistry::class)->all();

    expect($definitions)->toHaveCount(2)
        ->and(array_map(fn (WebhookEventDefinition $definition): string => $definition->name, $definitions))->toBe([
            'invoice.paid',
            'invoice.refunded',
        ]);

    $paid = $definitions[0];
    $refunded = $definitions[1];

    expect($paid->class)->toBe(InvoicePaidWebhook::class)
        ->and($paid->title)->toBe('Invoice Paid')
        ->and($paid->summary)->toBe('Sent when an invoice is paid.')
        ->and($paid->description)->toBe('Customers can subscribe to this webhook to react to paid invoices.')
        ->and($paid->tags)->toBe(['billing'])
        ->and($paid->attribute->name)->toBe('invoice.paid');

    expect($refunded->class)->toBe(InvoiceRefundedWebhook::class)
        ->and($refunded->title)->toBe('Invoice Refunded')
        ->and($refunded->tags)->toBe(['billing', 'refunds']);
});

it('rejects duplicate webhook event names', function (): void {
    config()->set('webhooks.scan_paths', [webhookFixturePath('DuplicateWebhooks')]);

    expect(fn () => app(WebhookEventRegistry::class)->all())
        ->toThrow(LogicException::class, 'Duplicate webhook event name [invoice.paid]');
});

it('discovers nested webhook event definitions recursively', function (): void {
    config()->set('webhooks.scan_paths', [webhookFixturePath('NestedRoot')]);

    $definitions = app(WebhookEventRegistry::class)->all();

    expect($definitions)->toHaveCount(1)
        ->and($definitions[0]->name)->toBe('invoice.voided')
        ->and($definitions[0]->class)->toBe(InvoiceVoidedWebhook::class);
});

it('caches default webhook definitions by class for singleton lookups', function (): void {
    config()->set('webhooks.scan_paths', [webhookFixturePath('Webhooks')]);

    $registry = app(WebhookEventRegistry::class);

    expect($registry->forClass(InvoicePaidWebhook::class)?->name)->toBe('invoice.paid')
        ->and($registry->forClass(stdClass::class))->toBeNull();

    config()->set('webhooks.scan_paths', []);

    expect($registry->forClass(InvoicePaidWebhook::class)?->name)->toBe('invoice.paid')
        ->and($registry->all())->toBe([]);
});

it('is registered as a singleton', function (): void {
    expect(app(WebhookEventRegistry::class))->toBe(app(WebhookEventRegistry::class));
});

function webhookFixturePath(string $directory): string
{
    return dirname(__DIR__).'/Fixtures/'.$directory;
}
