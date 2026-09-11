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
            $table->boolean('intended_as_npc')->default(false)->after('is_high_priority');
        });

        $now = now();

        foreach (['admin', 'designer'] as $role) {
            DB::table('role_capabilities')->updateOrInsert(
                ['role' => $role, 'capability' => 'design_npc'],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }

    public function down(): void
    {
        DB::table('role_capabilities')->where('capability', 'design_npc')->delete();

        Schema::table('horses', function (Blueprint $table) {
            $table->dropColumn('intended_as_npc');
        });
    }
};
