<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horses', function (Blueprint $table) {
            $table->string('sex')->nullable()->after('name');
            $table->index('sex');
        });
    }

    public function down(): void
    {
        Schema::table('horses', function (Blueprint $table) {
            $table->dropIndex(['sex']);
            $table->dropColumn('sex');
        });
    }
};
