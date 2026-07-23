<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeding_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sire_id')->constrained('horses')->cascadeOnDelete();
            $table->foreignId('dam_id')->constrained('horses')->cascadeOnDelete();
            $table->foreignId('sire_slot_id')->constrained('breeding_slots')->restrictOnDelete();
            $table->foreignId('dam_slot_id')->constrained('breeding_slots')->restrictOnDelete();
            $table->string('evidence_url');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending_staff');
            $table->json('result_options')->nullable();
            $table->unsignedTinyInteger('selected_option_index')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rolled_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('foal_id')->nullable()->constrained('horses')->nullOnDelete();
            $table->string('idempotency_key')->nullable()->unique();
            $table->string('genetics_provider')->nullable();
            $table->string('provider_request_id')->nullable();
            $table->json('provider_metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['requester_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeding_requests');
    }
};
