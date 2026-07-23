<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LifecycleRunLog extends Model
{
    protected $fillable = [
        'mode',
        'dry_run',
        'aged_count',
        'proposed_count',
        'survived_count',
        'skipped_count',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'dry_run' => 'boolean',
            'aged_count' => 'integer',
            'proposed_count' => 'integer',
            'survived_count' => 'integer',
            'skipped_count' => 'integer',
            'summary' => 'array',
        ];
    }
}
