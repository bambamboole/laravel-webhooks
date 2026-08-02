<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Bambamboole\LaravelWebhooks\Commands\CacheWebhookEventsCommand;
use Bambamboole\LaravelWebhooks\Commands\ClearWebhookEventsCommand;
use Bambamboole\LaravelWebhooks\Commands\ListWebhookEventsCommand;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

class WebhooksServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-webhooks')
            ->hasConfigFile()
            ->hasMigrations([
                'create_webhook_subscriptions_table',
                'create_webhook_deliveries_table',
            ])
            ->runsMigrations()
            ->hasCommands([
                CacheWebhookEventsCommand::class,
                ClearWebhookEventsCommand::class,
                ListWebhookEventsCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(WebhookEventRegistry::class);
        $this->app->singleton(WebhookPayloadFactory::class);
        $this->app->bindIf(WebhookSubscriptionRepository::class, DatabaseWebhookSubscriptionRepository::class);
    }

    public function packageBooted(): void
    {
        Event::listen(WebhookCallSucceededEvent::class, RecordWebhookDelivery::class);
        Event::listen(WebhookCallFailedEvent::class, RecordWebhookDelivery::class);
        Event::listen(FinalWebhookCallFailedEvent::class, RecordWebhookDelivery::class);

        if (config('webhooks.auto_listen', true)) {
            foreach ($this->app->make(WebhookEventRegistry::class)->all() as $definition) {
                Event::listen($definition->class, DispatchWebhookEvent::class);
            }
        }
    }
}
