<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lifecycle_settings', function (Blueprint $table) {
            $table->id();
            $table->date('horse_auto_age_next_update');
            $table->string('horse_auto_age_frequency_unit', 10);
            $table->unsignedTinyInteger('horse_auto_age_frequency_value');
            $table->decimal('horse_auto_age_game_years', 4, 2);
            $table->unsignedTinyInteger('horse_auto_health_roll_min')->default(0);
            $table->unsignedTinyInteger('horse_auto_health_roll_max')->default(100);
            $table->timestamps();
        });

        $defaultDate = Carbon::now()->addMonths(4)->format('Y-m-d');

        DB::table('lifecycle_settings')->insert([
            'horse_auto_age_next_update' => $defaultDate,
            'horse_auto_age_frequency_unit' => 'months',
            'horse_auto_age_frequency_value' => 4,
            'horse_auto_age_game_years' => 1,
            'horse_auto_health_roll_min' => 0,
            'horse_auto_health_roll_max' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lifecycle_settings');
    }
};
