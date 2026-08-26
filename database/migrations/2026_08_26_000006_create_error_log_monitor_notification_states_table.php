<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_log_monitor_notification_states', function (Blueprint $table): void {
            $table->id();
            $table->string('state_key')->unique();
            $table->string('type', 50)->index();
            $table->foreignId('issue_id')->nullable()->constrained('error_log_monitor_issues')->cascadeOnDelete();
            $table->boolean('active')->default(false);
            $table->timestamp('last_notified_at')->nullable();
            $table->unsignedInteger('notification_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_log_monitor_notification_states');
    }
};
