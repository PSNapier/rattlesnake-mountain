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
        Schema::table('horses', function (Blueprint $table) {
            $table->string('state')->default('pending')->after('id');
            $table->foreignId('public_horse_id')->nullable()->after('state');
        });

        Schema::table('horses', function (Blueprint $table) {
            $table->foreign('public_horse_id')->references('id')->on('horses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horses', function (Blueprint $table) {
            $table->dropForeign(['public_horse_id']);
            $table->dropColumn(['state', 'public_horse_id']);
        });
    }
};
