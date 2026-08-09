<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_log_monitor_issues', function (Blueprint $table): void {
            $table->id();

            $table->string('fingerprint', 64)->unique();

            $table->string('level', 20)->index();
            $table->string('exception_class')->nullable()->index();

            $table->text('normalized_message');

            $table->timestamp('first_seen_at')->nullable()->index();
            $table->timestamp('last_seen_at')->nullable()->index();

            $table->unsignedBigInteger('occurrences_count')->default(0);

            $table->string('last_file_path')->nullable();

            $table->longText('last_message')->nullable();
            $table->longText('last_context')->nullable();
            $table->longText('last_stack_trace')->nullable();

            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamp('ignored_at')->nullable()->index();

            $table->timestamp('last_notified_at')->nullable();
            $table->unsignedInteger('notification_count')->default(0);

            $table->timestamps();

            $table->index(['level', 'last_seen_at']);
            $table->index(['resolved_at', 'ignored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_log_monitor_issues');
    }
};
