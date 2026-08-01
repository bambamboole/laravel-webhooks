<?php

declare(strict_types=1);

return [
    // Directories scanned recursively for classes carrying the
    // #[WebhookEvent] attribute.
    'scan_paths' => [
        app_path('Events'),
    ],

    'dispatcher' => [
        // Sign calls with a timestamp to protect receivers against replay
        // attacks (requires spatie/laravel-webhook-server ^3.10).
        'use_timestamp' => true,
    ],
];
