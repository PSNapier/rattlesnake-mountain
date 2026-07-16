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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('frozen_at')->nullable()->after('role');
            $table->timestamp('banned_at')->nullable()->after('frozen_at');
            $table->timestamp('last_login_at')->nullable()->after('banned_at');
            $table->boolean('is_sanctuary')->default(false)->after('last_login_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['frozen_at', 'banned_at', 'last_login_at', 'is_sanctuary']);
            $table->dropSoftDeletes();
        });
    }
};
