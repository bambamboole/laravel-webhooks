<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Bambamboole\LaravelWebhooks\Support\ClassDiscoverer;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WebhooksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-webhooks')
            ->hasConfigFile()
            ->hasMigration('create_webhook_subscriptions_table')
            ->runsMigrations();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ClassDiscoverer::class);
        $this->app->singleton(WebhookEventRegistry::class);
        $this->app->singleton(WebhookPayloadFactory::class);
        $this->app->bindIf(WebhookSubscriptionRepository::class, DatabaseWebhookSubscriptionRepository::class);
    }
}
