<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('error_log_monitor_normalization_rules', function (Blueprint $table): void {
            $table->string('type', 20)->default('regex')->after('name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('error_log_monitor_normalization_rules', function (Blueprint $table): void {
            $table->dropIndex(['type']);
        });

        Schema::table('error_log_monitor_normalization_rules', function (Blueprint $table): void {
            $table->dropColumn('type');
        });
    }
};
