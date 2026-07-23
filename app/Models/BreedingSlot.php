<?php

namespace App\Models;

use App\Enums\BreedingSlotStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BreedingSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'horse_id',
        'sequence',
        'holder_id',
        'status',
        'reserved_for_request_id',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'status' => BreedingSlotStatus::class,
            'consumed_at' => 'datetime',
        ];
    }

    public function horse(): BelongsTo
    {
        return $this->belongsTo(Horse::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_id');
    }

    public function reservedForRequest(): BelongsTo
    {
        return $this->belongsTo(BreedingRequest::class, 'reserved_for_request_id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(BreedingSlotTransfer::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === BreedingSlotStatus::Available;
    }
}
