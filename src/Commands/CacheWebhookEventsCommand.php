<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Commands;

use Bambamboole\LaravelWebhooks\WebhookEventRegistry;
use Illuminate\Console\Command;

final class CacheWebhookEventsCommand extends Command
{
    protected $signature = 'webhooks:cache';

    protected $description = 'Cache the discovered webhook events for faster boot';

    public function handle(WebhookEventRegistry $registry): int
    {
        $definitions = $registry->discover();

        file_put_contents(
            $registry->cachePath(),
            '<?php return '.var_export(array_map(get_object_vars(...), $definitions), true).';'.PHP_EOL,
        );

        $this->components->info(sprintf('Cached %d webhook events.', count($definitions)));

        return self::SUCCESS;
    }
}
