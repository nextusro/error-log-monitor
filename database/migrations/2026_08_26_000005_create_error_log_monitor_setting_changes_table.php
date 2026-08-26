<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_log_monitor_setting_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('setting_id')
                ->nullable()
                ->constrained('error_log_monitor_settings')
                ->nullOnDelete();
            $table->string('group', 100);
            $table->string('key', 150);
            $table->json('old_value')->nullable();
            $table->json('new_value');
            $table->string('changed_by_id')->nullable();
            $table->string('changed_by_name')->nullable();
            $table->timestamps();

            $table->index(['group', 'key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_log_monitor_setting_changes');
    }
};
