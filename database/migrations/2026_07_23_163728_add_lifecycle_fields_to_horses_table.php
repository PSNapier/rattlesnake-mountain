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
            $table->unsignedInteger('age_months')->default(0)->after('age');
            $table->boolean('is_npc')->default(false)->after('equipment');
            $table->boolean('is_claimable')->default(false)->after('is_npc');
            $table->timestamp('died_at')->nullable()->after('is_claimable');
        });

        DB::table('horses')->orderBy('id')->chunkById(100, function ($horses): void {
            foreach ($horses as $horse) {
                DB::table('horses')
                    ->where('id', $horse->id)
                    ->update(['age_months' => ((int) $horse->age) * 12]);
            }
        });

        Schema::table('horses', function (Blueprint $table) {
            $table->dropColumn('age');
        });

        $sanctuaryId = DB::table('users')->where('is_sanctuary', true)->value('id');

        if ($sanctuaryId) {
            DB::table('horses')
                ->where('owner_id', $sanctuaryId)
                ->update([
                    'is_npc' => true,
                    'is_claimable' => true,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('horses', function (Blueprint $table) {
            $table->integer('age')->default(0)->after('progeny');
        });

        DB::table('horses')->orderBy('id')->chunkById(100, function ($horses): void {
            foreach ($horses as $horse) {
                DB::table('horses')
                    ->where('id', $horse->id)
                    ->update(['age' => intdiv((int) $horse->age_months, 12)]);
            }
        });

        Schema::table('horses', function (Blueprint $table) {
            $table->dropColumn(['age_months', 'is_npc', 'is_claimable', 'died_at']);
        });
    }
};
