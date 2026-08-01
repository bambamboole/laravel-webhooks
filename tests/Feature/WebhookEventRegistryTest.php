<?php

declare(strict_types=1);

use Bambamboole\LaravelWebhooks\Support\ClassDiscoverer;
use Bambamboole\LaravelWebhooks\Tests\Fixtures\NestedRoot\Nested\InvoiceVoidedWebhook;
use Bambamboole\LaravelWebhooks\Tests\Fixtures\Webhooks\InvoicePaidWebhook;
use Bambamboole\LaravelWebhooks\Tests\Fixtures\Webhooks\InvoiceRefundedWebhook;
use Bambamboole\LaravelWebhooks\WebhookEventDefinition;
use Bambamboole\LaravelWebhooks\WebhookEventRegistry;

it('discovers webhook event definitions sorted by event name', function (): void {
    config()->set('webhooks.scan_paths', [webhookFixturePath('Webhooks')]);

    $definitions = freshWebhookEventRegistry()->all();

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

    expect(fn (): array => freshWebhookEventRegistry()->all())
        ->toThrow(LogicException::class, 'Duplicate webhook event name [invoice.paid]');
});

it('discovers nested webhook event definitions recursively', function (): void {
    config()->set('webhooks.scan_paths', [webhookFixturePath('NestedRoot')]);

    $definitions = freshWebhookEventRegistry()->all();

    expect($definitions)->toHaveCount(1)
        ->and($definitions[0]->name)->toBe('invoice.voided')
        ->and($definitions[0]->class)->toBe(InvoiceVoidedWebhook::class);
});

it('memoizes discovery so config changes after first use are ignored', function (): void {
    config()->set('webhooks.scan_paths', [webhookFixturePath('Webhooks')]);

    $registry = freshWebhookEventRegistry();

    expect($registry->forClass(InvoicePaidWebhook::class)?->name)->toBe('invoice.paid')
        ->and($registry->forClass(stdClass::class))->toBeNull();

    config()->set('webhooks.scan_paths', []);

    expect($registry->forClass(InvoicePaidWebhook::class)?->name)->toBe('invoice.paid')
        ->and($registry->all())->toHaveCount(2);
});

it('discovers nothing when no scan paths are configured', function (): void {
    config()->set('webhooks.scan_paths', []);

    expect(freshWebhookEventRegistry()->all())->toBe([]);
});

it('is registered as a singleton', function (): void {
    expect(app(WebhookEventRegistry::class))->toBe(app(WebhookEventRegistry::class));
});

function freshWebhookEventRegistry(): WebhookEventRegistry
{
    return new WebhookEventRegistry(new ClassDiscoverer);
}

function webhookFixturePath(string $directory): string
{
    return dirname(__DIR__).'/Fixtures/'.$directory;
}
