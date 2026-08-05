<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            // Plain string on purpose: custom subscription repositories may
            // yield ids that are not uuids of webhook_subscriptions rows.
            $table->string('subscription_id')->nullable()->index();
            $table->uuid('call_uuid')->index();
            $table->string('event')->index();
            $table->string('url');
            $table->string('http_verb');
            $table->json('payload');
            $table->unsignedSmallInteger('attempt');
            $table->string('status')->index();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->string('error_type')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
