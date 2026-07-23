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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('type')->default('horse_submission')->after('id');
            $table->foreignId('breeding_request_id')
                ->nullable()
                ->after('horse_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['horse_id']);
            $table->dropForeign(['admin_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('horse_id')->nullable()->change();
            $table->unsignedBigInteger('admin_id')->nullable()->change();
            $table->foreign('horse_id')->references('id')->on('horses')->cascadeOnDelete();
            $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('breeding_request_id');
            $table->dropColumn('type');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['horse_id']);
            $table->dropForeign(['admin_id']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedBigInteger('horse_id')->nullable(false)->change();
            $table->unsignedBigInteger('admin_id')->nullable(false)->change();
            $table->foreign('horse_id')->references('id')->on('horses')->cascadeOnDelete();
            $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
