<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Herd extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'created_by',
        'name',
        'herd_leader_id',
        'herd_members',
        'inventory',
        'equipment',
    ];

    protected function casts(): array
    {
        return [
            'herd_members' => 'array',
            'inventory' => 'array',
            'equipment' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function herdLeader(): BelongsTo
    {
        return $this->belongsTo(Horse::class, 'herd_leader_id');
    }

    public function horses(): HasMany
    {
        return $this->hasMany(Horse::class);
    }
}
