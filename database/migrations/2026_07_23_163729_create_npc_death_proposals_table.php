<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('npc_death_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('horse_id')->constrained('horses')->cascadeOnDelete();
            $table->timestamp('rolled_at');
            $table->unsignedInteger('age_months_at_roll');
            $table->unsignedTinyInteger('chance_percent');
            $table->string('status', 20);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'horse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('npc_death_proposals');
    }
};
