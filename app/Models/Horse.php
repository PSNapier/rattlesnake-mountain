<?php

namespace App\Models;

use App\Enums\HorseState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'state',
        'public_horse_id',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'bloodline' => 'array',
            'progeny' => 'array',
            'stats' => 'array',
            'inventory' => 'array',
            'equipment' => 'array',
            'state' => HorseState::class,
            'approved_at' => 'datetime',
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

    public function publicHorse(): BelongsTo
    {
        return $this->belongsTo(Horse::class, 'public_horse_id');
    }

    public function pendingVersions(): HasMany
    {
        return $this->hasMany(Horse::class, 'public_horse_id');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('state', HorseState::Public);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('state', HorseState::Pending);
    }

    public function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('state', HorseState::Public);

            if ($user) {
                $q->orWhere(function (Builder $subQ) use ($user) {
                    $subQ->where('state', HorseState::Pending);
                    if ($user->isAdmin()) {
                        // Admins can see all pending horses
                        return;
                    }
                    // Non-admins can only see their own pending horses
                    $subQ->where('owner_id', $user->id);
                });
            }
        });
    }
}
