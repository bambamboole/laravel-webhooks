<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('defines nullable tenant UUIDs on both tables', function (): void {
    $columns = [];

    Schema::shouldReceive('create')
        ->twice()
        ->andReturnUsing(function (string $table, Closure $callback) use (&$columns): void {
            $blueprint = new Blueprint(DB::connection(), $table);
            $callback($blueprint);
            $columns[$table] = collect($blueprint->getColumns())->keyBy('name');
        });

    foreach (['subscriptions', 'deliveries'] as $table) {
        $migration = require dirname(__DIR__, 2)."/database/migrations/create_webhook_{$table}_table.php";
        $migration->up();
    }

    foreach (['webhook_subscriptions', 'webhook_deliveries'] as $table) {
        expect($columns[$table])->toHaveKey('tenant_id');

        $tenantId = $columns[$table]['tenant_id'];

        expect($tenantId->get('type'))->toBe('uuid')
            ->and($tenantId->get('nullable'))->toBeTrue();
    }
});
