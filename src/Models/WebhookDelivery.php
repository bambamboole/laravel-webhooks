<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string|null $subscription_id
 * @property string $call_uuid
 * @property string $event
 * @property string $url
 * @property string $http_verb
 * @property array<string, mixed> $payload
 * @property int $attempt
 * @property string $status
 * @property int|null $response_status
 * @property string|null $error_type
 * @property string|null $error_message
 */
class WebhookDelivery extends Model
{
    use HasUuids;

    public const string STATUS_SUCCEEDED = 'succeeded';

    public const string STATUS_FAILED = 'failed';

    public const string STATUS_FINAL_FAILED = 'final_failed';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempt' => 'integer',
            'response_status' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<WebhookSubscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }
}
