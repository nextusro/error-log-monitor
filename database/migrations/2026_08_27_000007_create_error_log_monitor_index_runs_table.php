<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_log_monitor_index_runs', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('started_at')->index();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('status', 20)->index();
            $table->string('stop_reason', 30)->nullable()->index();
            $table->unsignedInteger('discovered_files')->default(0);
            $table->unsignedInteger('processed_files')->default(0);
            $table->unsignedInteger('pending_files')->default(0);
            $table->unsignedInteger('partially_processed_files')->default(0);
            $table->unsignedInteger('failed_files')->default(0);
            $table->unsignedBigInteger('processed_lines')->default(0);
            $table->unsignedBigInteger('parsed_entries')->default(0);
            $table->unsignedBigInteger('indexed_issues')->default(0);
            $table->string('start_cursor')->nullable();
            $table->string('end_cursor')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_log_monitor_index_runs');
    }
};
