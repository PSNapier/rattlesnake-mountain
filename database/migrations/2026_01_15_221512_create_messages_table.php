<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // recipient (owner)
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete(); // sender (admin)
            $table->string('subject');
            $table->text('initial_message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('status')->default('pending'); // pending, accepted, declined
            $table->timestamp('responded_at')->nullable();

            // Admin edits (stored as JSON for flexibility)
            $table->json('admin_edits')->nullable(); // {name, age, geno, herd_id, design_link}

            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['horse_id']);
        });

        Schema::create('message_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['message_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_comments');
        Schema::dropIfExists('messages');
    }
};
