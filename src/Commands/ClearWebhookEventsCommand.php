<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Commands;

use Bambamboole\LaravelWebhooks\WebhookEventRegistry;
use Illuminate\Console\Command;

final class ClearWebhookEventsCommand extends Command
{
    protected $signature = 'webhooks:clear';

    protected $description = 'Remove the webhook events cache';

    public function handle(WebhookEventRegistry $registry): int
    {
        $path = $registry->cachePath();

        if (is_file($path)) {
            unlink($path);
        }

        $this->components->info('Webhook events cache cleared.');

        return self::SUCCESS;
    }
}
