<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeding_slot_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('breeding_slot_id')->constrained('breeding_slots')->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('acted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['to_user_id', 'status']);
            $table->index(['from_user_id', 'status']);
            $table->index(['breeding_slot_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_slot_transfers');
    }
};
