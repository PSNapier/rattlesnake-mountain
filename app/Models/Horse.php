<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Horse extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'bred_by',
        'name',
        'bloodline',
        'progeny',
        'age',
        'design_link',
        'stats',
        'geno',
        'herd_id',
        'inventory',
        'equipment',
    ];

    protected function casts(): array
    {
        return [
            'bloodline' => 'array',
            'progeny' => 'array',
            'stats' => 'array',
            'inventory' => 'array',
            'equipment' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function bredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bred_by');
    }

    public function herd(): BelongsTo
    {
        return $this->belongsTo(Herd::class);
    }
}
