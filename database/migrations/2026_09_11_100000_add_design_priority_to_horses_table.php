<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('horses', function (Blueprint $table) {
            $table->boolean('is_high_priority')->default(false)->after('is_claimable');
        });

        $now = now();

        foreach (['admin', 'designer'] as $role) {
            DB::table('role_capabilities')->updateOrInsert(
                ['role' => $role, 'capability' => 'design_priority'],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('role_capabilities')->where('capability', 'design_priority')->delete();

        Schema::table('horses', function (Blueprint $table) {
            $table->dropColumn('is_high_priority');
        });
    }
};
