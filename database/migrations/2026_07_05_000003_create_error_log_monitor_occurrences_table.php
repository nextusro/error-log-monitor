<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_log_monitor_occurrences', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('issue_id')
                ->constrained('error_log_monitor_issues')
                ->cascadeOnDelete();

            $table->foreignId('file_id')
                ->nullable()
                ->constrained('error_log_monitor_files')
                ->nullOnDelete();

            $table->string('level', 20)->index();

            $table->string('file_path_snapshot')->nullable();

            $table->longText('message');
            $table->longText('context')->nullable();
            $table->longText('stack_trace')->nullable();

            $table->timestamp('occurred_at')->nullable()->index();

            $table->timestamps();

            $table->index(['issue_id', 'occurred_at']);
            $table->index(['level', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_log_monitor_occurrences');
    }
};
