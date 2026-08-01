<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class WebhookEvent
{
    /**
     * @param  list<string>  $tags
     */
    public function __construct(
        public string $name,
        public ?string $title = null,
        public ?string $summary = null,
        public ?string $description = null,
        public array $tags = [],
    ) {}
}
