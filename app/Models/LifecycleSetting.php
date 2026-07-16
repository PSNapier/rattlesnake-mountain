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
    ];

    protected function casts(): array
    {
        return [
            'horse_auto_age_next_update' => 'date',
            'horse_auto_age_frequency_value' => 'integer',
            'horse_auto_age_game_years' => 'float',
            'horse_auto_health_roll_min' => 'integer',
            'horse_auto_health_roll_max' => 'integer',
        ];
    }
}
