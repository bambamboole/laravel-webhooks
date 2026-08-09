<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Bambamboole\LaravelWebhooks\Commands\CacheWebhookEventsCommand;
use Bambamboole\LaravelWebhooks\Commands\ClearWebhookEventsCommand;
use Bambamboole\LaravelWebhooks\Commands\ListWebhookEventsCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

class WebhooksServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/webhooks.php', 'webhooks');

        $this->app->singleton(WebhookEventRegistry::class);
        $this->app->singleton(WebhookPayloadFactory::class);
        $this->app->bindIf(WebhookSubscriptionRepository::class, DatabaseWebhookSubscriptionRepository::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/webhooks.php' => $this->app->configPath('webhooks.php'),
            ], 'webhooks-config');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'webhooks-migrations');

            $this->commands([
                CacheWebhookEventsCommand::class,
                ClearWebhookEventsCommand::class,
                ListWebhookEventsCommand::class,
            ]);
        }

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
