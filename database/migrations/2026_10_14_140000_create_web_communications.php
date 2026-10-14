<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_push_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->uuid('app_id');
            $t->uuid('subscription_id');
            $t->string('label', 120);
            $t->timestamp('consented_at');
            $t->timestamp('verified_at');
            $t->timestamp('revoked_at')->nullable();
            $t->timestamp('provider_revoked_at')->nullable();
            $t->timestamps();
            $t->unique(['app_id', 'subscription_id']);
            $t->index(['user_id', 'revoked_at']);
        });
        Schema::create('communication_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('submission_key');
            $t->string('channel', 10);
            $t->string('title', 160);
            $t->text('body');
            $t->string('locale', 10);
            $t->json('recipient_ids');
            $t->timestamps();
            $t->unique(['actor_id', 'submission_key']);
        });
        Schema::create('communication_deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->nullable()->constrained('communication_batches')->restrictOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->uuid('idempotency_key')->unique();
            $t->string('source_key', 100)->nullable()->unique();
            $t->string('channel', 10);
            $t->string('title', 160);
            $t->text('body');
            $t->string('locale', 10);
            $t->string('path', 255)->default('/account/notifications');
            $t->string('status', 20)->default('pending');
            $t->timestamp('available_at');
            $t->timestamp('claimed_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->string('provider_id', 100)->nullable();
            $t->string('error_code', 80)->nullable();
            $t->timestamps();
            $t->unique(['batch_id', 'user_id']);
            $t->index(['status', 'available_at']);
            $t->index(['user_id', 'channel', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_deliveries');
        Schema::dropIfExists('communication_batches');
        Schema::dropIfExists('web_push_subscriptions');
    }
};
