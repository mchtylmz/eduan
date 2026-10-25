<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->boolean('is_live')->default(false);
            $table->json('calculation_settings')->nullable();
            $table->timestamp('calculated_through_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->index(['is_live', 'closed_at'], 'leagues_live_open_index');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropIndex('leagues_live_open_index');
            $table->dropColumn(['is_live', 'calculation_settings', 'calculated_through_at', 'closed_at']);
        });
    }
};
