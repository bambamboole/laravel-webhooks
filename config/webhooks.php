<?php

declare(strict_types=1);
use Bambamboole\LaravelWebhooks\Models\WebhookDelivery;
use Bambamboole\LaravelWebhooks\Models\WebhookSubscription;

return [
    // Directories scanned recursively for classes carrying the
    // #[WebhookEvent] attribute.
    'scan_paths' => [
        app_path('Events'),
    ],

    // Automatically register DispatchWebhookEvent as a listener for every
    // discovered webhook event class. Discovery scans the paths above at
    // every boot unless `php artisan webhooks:cache` has been run.
    'auto_listen' => true,

    'dispatcher' => [
        // Sign calls with a timestamp to protect receivers against replay
        // attacks (requires spatie/laravel-webhook-server ^3.10).
        'use_timestamp' => true,
    ],

    // Swap in app-level subclasses (e.g. to add tenancy or global scopes).
    // Each class must extend the package model it replaces.
    'models' => [
        'subscription' => WebhookSubscription::class,
        'delivery' => WebhookDelivery::class,
    ],

    'deliveries' => [
        // Delivery log rows older than this many days are removed when the
        // app schedules `model:prune`. Null keeps them forever.
        'prune_after_days' => 30,
    ],
];
