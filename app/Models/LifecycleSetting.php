<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LifecycleSetting extends Model
{
    protected $fillable = [
        'horse_auto_age_next_update',
        'horse_auto_age_frequency_unit',
        'horse_auto_age_frequency_value',
        'horse_auto_age_game_years',
        'horse_auto_health_roll_min',
        'horse_auto_health_roll_max',
        'npc_death_age_threshold',
        'npc_death_base_percent',
        'npc_death_double_every_years',
        'npc_death_cap_percent',
    ];

    protected function casts(): array
    {
        return [
            'horse_auto_age_next_update' => 'date',
            'horse_auto_age_frequency_value' => 'integer',
            'horse_auto_age_game_years' => 'float',
            'horse_auto_health_roll_min' => 'integer',
            'horse_auto_health_roll_max' => 'integer',
            'npc_death_age_threshold' => 'integer',
            'npc_death_base_percent' => 'integer',
            'npc_death_double_every_years' => 'integer',
            'npc_death_cap_percent' => 'integer',
        ];
    }

    public function monthsToAgePerCycle(): int
    {
        return (int) round(((float) $this->horse_auto_age_game_years) * 12);
    }

    public function deathChancePercent(int $ageMonths): ?int
    {
        $ageYears = intdiv($ageMonths, 12);
        $threshold = (int) $this->npc_death_age_threshold;

        if ($ageYears <= $threshold) {
            return null;
        }

        $base = (int) $this->npc_death_base_percent;
        $doubleEvery = max(1, (int) $this->npc_death_double_every_years);
        $cap = (int) $this->npc_death_cap_percent;
        $steps = intdiv($ageYears - ($threshold + 1), $doubleEvery);

        return min($cap, $base * (2 ** $steps));
    }
}
