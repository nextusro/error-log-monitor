<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('error_log_monitor_issues', function (Blueprint $table): void {
            $table->string('pending_fingerprint', 64)->nullable()->unique();
            $table->uuid('regrouping_token')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('error_log_monitor_issues', function (Blueprint $table): void {
            $table->dropUnique(['pending_fingerprint']);
            $table->dropIndex(['regrouping_token']);
        });

        Schema::table('error_log_monitor_issues', function (Blueprint $table): void {
            $table->dropColumn(['pending_fingerprint', 'regrouping_token']);
        });
    }
};
