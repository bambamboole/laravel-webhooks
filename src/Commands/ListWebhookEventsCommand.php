<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Commands;

use Bambamboole\LaravelWebhooks\WebhookEventDefinition;
use Bambamboole\LaravelWebhooks\WebhookEventRegistry;
use Illuminate\Console\Command;

final class ListWebhookEventsCommand extends Command
{
    protected $signature = 'webhooks:events';

    protected $description = 'List the discovered webhook events';

    public function handle(WebhookEventRegistry $registry): int
    {
        $definitions = $registry->all();

        if ($definitions === []) {
            $this->components->info('No webhook events discovered.');

            return self::SUCCESS;
        }

        $this->table(
            ['Event', 'Title', 'Class'],
            array_map(fn (WebhookEventDefinition $definition): array => [
                $definition->name,
                $definition->title ?? '',
                $definition->class,
            ], $definitions),
        );

        return self::SUCCESS;
    }
}
