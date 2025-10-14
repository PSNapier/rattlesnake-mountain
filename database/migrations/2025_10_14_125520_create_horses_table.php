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
        Schema::create('horses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('bred_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->json('bloodline')->nullable();
            $table->json('progeny')->nullable();
            $table->integer('age');
            $table->string('design_link')->nullable();
            $table->json('stats')->nullable();
            $table->string('geno');
            $table->foreignId('herd_id')->nullable()->constrained('herds')->onDelete('set null');
            $table->json('inventory')->nullable();
            $table->json('equipment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horses');
    }
};
