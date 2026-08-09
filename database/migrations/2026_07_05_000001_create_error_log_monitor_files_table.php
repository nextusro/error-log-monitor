<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_log_monitor_files', function (Blueprint $table): void {
            $table->id();
            $table->string('path')->unique();
            $table->string('relative_path')->index();
            $table->string('directory')->nullable()->index();
            $table->string('filename')->index();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedBigInteger('last_offset')->default(0);
            $table->unsignedBigInteger('inode')->nullable()->index();
            $table->timestamp('last_modified_at')->nullable();
            $table->timestamp('last_scanned_at')->nullable();
            $table->boolean('is_missing')->default(false)->index();
            $table->timestamp('missing_since')->nullable();
            $table->timestamp('was_truncated_at')->nullable();
            $table->timestamps();

            $table->index(['directory', 'filename']);
            $table->index(['is_missing', 'last_scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_log_monitor_files');
    }
};
