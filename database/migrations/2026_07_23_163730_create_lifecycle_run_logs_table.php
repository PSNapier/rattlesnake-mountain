<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lifecycle_run_logs', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 20);
            $table->boolean('dry_run')->default(false);
            $table->unsignedInteger('aged_count')->default(0);
            $table->unsignedInteger('proposed_count')->default(0);
            $table->unsignedInteger('survived_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->json('summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lifecycle_run_logs');
    }
};
