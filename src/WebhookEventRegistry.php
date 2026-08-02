<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks;

use Bambamboole\LaravelWebhooks\Attributes\WebhookEvent;
use Bambamboole\LaravelWebhooks\Support\ClassDiscoverer;
use LogicException;
use ReflectionClass;

final class WebhookEventRegistry
{
    /**
     * @var list<WebhookEventDefinition>|null
     */
    private ?array $definitions = null;

    public function __construct(
        private readonly ClassDiscoverer $classes,
    ) {}

    /**
     * @param  class-string  $class
     */
    public function forClass(string $class): ?WebhookEventDefinition
    {
        return array_find(
            $this->all(),
            fn (WebhookEventDefinition $definition): bool => $definition->class === $class,
        );
    }

    /**
     * @return list<WebhookEventDefinition>
     */
    public function all(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        return $this->definitions = $this->loadCachedDefinitions() ?? $this->discover();
    }

    /**
     * Scan the configured paths, ignoring the memo and the cache file.
     *
     * @return list<WebhookEventDefinition>
     */
    public function discover(): array
    {
        $definitions = [];

        foreach ($this->classes->classesIn($this->scanPaths()) as $class) {
            $reflection = new ReflectionClass($class);

            if (! $reflection->isInstantiable()) {
                continue;
            }

            $attributes = $reflection->getAttributes(WebhookEvent::class);

            if ($attributes === []) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();

            if (isset($definitions[$attribute->name])) {
                throw new LogicException("Duplicate webhook event name [{$attribute->name}]");
            }

            $definitions[$attribute->name] = new WebhookEventDefinition(
                name: $attribute->name,
                class: $class,
                title: $attribute->title,
                summary: $attribute->summary,
                description: $attribute->description,
                tags: $attribute->tags,
            );
        }

        ksort($definitions);

        return array_values($definitions);
    }

    public function cachePath(): string
    {
        return app()->bootstrapPath('cache/webhooks.php');
    }

    /**
     * @return list<WebhookEventDefinition>|null
     */
    private function loadCachedDefinitions(): ?array
    {
        if (! is_file($this->cachePath())) {
            return null;
        }

        /** @var list<array{name: string, class: class-string, title: string|null, summary: string|null, description: string|null, tags: list<string>}> $rows */
        $rows = require $this->cachePath();

        return array_map(
            fn (array $row): WebhookEventDefinition => new WebhookEventDefinition(...$row),
            $rows,
        );
    }

    /**
     * @return list<string>
     */
    private function scanPaths(): array
    {
        $paths = config('webhooks.scan_paths', []);

        if (! is_array($paths)) {
            return [];
        }

        return array_values(array_filter($paths, is_string(...)));
    }
}
