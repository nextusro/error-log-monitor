<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_log_monitor_grouping_states', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('rules_version')->default(0);
            $table->unsignedBigInteger('grouped_rules_version')->default(0);
            $table->timestamp('regroup_requested_at')->nullable();
            $table->timestamp('last_regrouped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_log_monitor_grouping_states');
    }
};
