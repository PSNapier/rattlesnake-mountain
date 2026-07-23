<?php

namespace App\Models;

use App\Enums\HorseSex;
use App\Enums\HorseState;
use App\Enums\NpcDeathProposalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Horse extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'bred_by',
        'name',
        'sex',
        'bloodline',
        'progeny',
        'age_months',
        'design_link',
        'stats',
        'geno',
        'herd_id',
        'inventory',
        'equipment',
        'is_npc',
        'is_claimable',
        'died_at',
        'state',
        'public_horse_id',
        'approved_at',
        'archived_at',
        'contacted_at',
    ];

    protected $appends = [
        'age_years',
        'age_months_part',
        'formatted_age',
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
            'sex' => HorseSex::class,
            'is_npc' => 'boolean',
            'is_claimable' => 'boolean',
            'age_months' => 'integer',
            'approved_at' => 'datetime',
            'archived_at' => 'datetime',
            'contacted_at' => 'datetime',
            'died_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Horse $horse): void {
            if ($horse->isDirty('owner_id')) {
                $horse->syncNpcFlagsFromOwner(voidProposals: false);
            }
        });

        static::saved(function (Horse $horse): void {
            if ($horse->wasChanged('owner_id') && ! $horse->is_npc) {
                $horse->voidPendingDeathProposals();
            }
        });
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

    public function adminLogs(): HasMany
    {
        return $this->hasMany(AdminSubmissionLog::class);
    }

    public function latestAdminLog(): HasOne
    {
        return $this->hasOne(AdminSubmissionLog::class)->latestOfMany();
    }

    public function deathProposals(): HasMany
    {
        return $this->hasMany(NpcDeathProposal::class);
    }

    public function breedingSlots(): HasMany
    {
        return $this->hasMany(BreedingSlot::class);
    }

    public function getAgeYearsAttribute(): int
    {
        return intdiv((int) $this->age_months, 12);
    }

    public function getAgeMonthsPartAttribute(): int
    {
        return ((int) $this->age_months) % 12;
    }

    public static function formatAgeMonths(int $ageMonths): string
    {
        $years = intdiv($ageMonths, 12);
        $months = $ageMonths % 12;

        $yearLabel = $years === 1 ? '1 year' : "{$years} years";
        $monthLabel = $months === 1 ? '1 month' : "{$months} months";

        if ($months === 0) {
            return $yearLabel;
        }

        if ($years === 0) {
            return $monthLabel;
        }

        return "{$yearLabel}, {$monthLabel}";
    }

    public function getFormattedAgeAttribute(): string
    {
        return self::formatAgeMonths((int) $this->age_months);
    }

    public static function monthsFromYearsAndMonths(int $years, int $months): int
    {
        return ($years * 12) + $months;
    }

    public function syncNpcFlagsFromOwner(bool $voidProposals = true): void
    {
        $sanctuaryId = User::query()->where('is_sanctuary', true)->value('id');
        $isSanctuaryOwned = $sanctuaryId !== null && (int) $this->owner_id === (int) $sanctuaryId;

        if ($isSanctuaryOwned) {
            $this->is_npc = true;
            if (! $this->exists || $this->isDirty('owner_id')) {
                $this->is_claimable = true;
            }

            return;
        }

        $wasNpc = $this->is_npc;
        $this->is_npc = false;

        if ($voidProposals && $wasNpc) {
            $this->voidPendingDeathProposals();
        }
    }

    public function voidPendingDeathProposals(): void
    {
        $this->deathProposals()
            ->where('status', NpcDeathProposalStatus::Pending)
            ->update([
                'status' => NpcDeathProposalStatus::Voided,
                'resolved_at' => now(),
            ]);
    }

    public function isAlive(): bool
    {
        return $this->died_at === null;
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('state', HorseState::Public);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('state', HorseState::Pending);
    }

    public function scopeAlive(Builder $query): Builder
    {
        return $query->whereNull('died_at');
    }

    public function scopeActivePending(Builder $query): Builder
    {
        return $query
            ->where('state', HorseState::Pending)
            ->whereNull('approved_at')
            ->whereNull('archived_at');
    }

    public function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('state', HorseState::Public);

            if ($user) {
                $q->orWhere(function (Builder $subQ) use ($user) {
                    $subQ->where('state', HorseState::Pending);
                    if ($user->hasCapability('submissions')) {
                        // Staff with submissions access can see all pending horses
                        return;
                    }
                    // Non-admins can only see their own pending horses
                    $subQ->where('owner_id', $user->id);
                });
            }
        });
    }

    public function isPublic(): bool
    {
        return $this->state === HorseState::Public;
    }

    public function isPending(): bool
    {
        return $this->state === HorseState::Pending;
    }

    public function isActivePending(): bool
    {
        return $this->isPending()
            && $this->approved_at === null
            && $this->archived_at === null;
    }

    public function isHistoricalPending(): bool
    {
        return $this->isPending()
            && ($this->approved_at !== null || $this->archived_at !== null);
    }

    public function hasActivePendingEdit(): bool
    {
        return $this->activePendingEdit() !== null;
    }

    public function activePendingEdit(): ?Horse
    {
        if (! $this->isPublic()) {
            return null;
        }

        return $this->pendingVersions()
            ->activePending()
            ->first();
    }
}
