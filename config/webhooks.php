<?php

declare(strict_types=1);

return [
    // Directories scanned recursively for classes carrying the
    // #[WebhookEvent] attribute.
    'scan_paths' => [
        app_path('Events'),
    ],

    // Automatically register DispatchWebhookEvent as a listener for every
    // discovered webhook event class. Discovery scans the paths above at
    // every boot; disable this and wire listeners manually if boot cost
    // matters.
    'auto_listen' => true,

    'dispatcher' => [
        // Sign calls with a timestamp to protect receivers against replay
        // attacks (requires spatie/laravel-webhook-server ^3.10).
        'use_timestamp' => true,
    ],

    'deliveries' => [
        // Delivery log rows older than this many days are removed when the
        // app schedules `model:prune`. Null keeps them forever.
        'prune_after_days' => 30,
    ],
];
