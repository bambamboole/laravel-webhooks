<?php

declare(strict_types=1);

namespace Bambamboole\LaravelWebhooks\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WebhookDelivery extends Model
{
    use HasUuids;
    use MassPrunable;

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

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        $days = config('webhooks.deliveries.prune_after_days');

        if ($days === null) {
            return static::query()->whereRaw('1 = 0');
        }

        return static::query()->where('created_at', '<', now()->subDays((int) $days));
    }
}
