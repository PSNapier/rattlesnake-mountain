<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lifecycle_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('npc_death_age_threshold')->default(15)->after('horse_auto_health_roll_max');
            $table->unsignedTinyInteger('npc_death_base_percent')->default(2)->after('npc_death_age_threshold');
            $table->unsignedTinyInteger('npc_death_double_every_years')->default(2)->after('npc_death_base_percent');
            $table->unsignedTinyInteger('npc_death_cap_percent')->default(95)->after('npc_death_double_every_years');
        });
    }

    public function down(): void
    {
        Schema::table('lifecycle_settings', function (Blueprint $table) {
            $table->dropColumn([
                'npc_death_age_threshold',
                'npc_death_base_percent',
                'npc_death_double_every_years',
                'npc_death_cap_percent',
            ]);
        });
    }
};
