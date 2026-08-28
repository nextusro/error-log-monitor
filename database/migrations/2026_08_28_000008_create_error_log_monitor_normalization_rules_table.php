<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_log_monitor_normalization_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('pattern');
            $table->text('replacement');
            $table->unsignedInteger('priority')->default(100)->index();
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_log_monitor_normalization_rules');
    }
};
