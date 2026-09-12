<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inline editing publishes straight to the live page, so the safety net is
     * behind it rather than in front: every save pushes the pre-save state in
     * here first. The list is pruned to the ten newest per page, which is
     * enough to undo a bad afternoon without growing without bound.
     */
    public function up(): void
    {
        Schema::create('cms_page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cms_page_id')->constrained()->cascadeOnDelete();

            // The author can be deleted without taking the history with them.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('hero_title');
            $table->text('hero_description')->nullable();
            $table->json('content');
            $table->boolean('coming_soon')->default(false);
            $table->timestamps();

            $table->index(['cms_page_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_page_revisions');
    }
};
