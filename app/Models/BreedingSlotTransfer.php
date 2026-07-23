<?php

namespace App\Models;

use App\Enums\BreedingSlotTransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreedingSlotTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'breeding_slot_id',
        'from_user_id',
        'to_user_id',
        'acted_by_id',
        'status',
        'notes',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BreedingSlotTransferStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(BreedingSlot::class, 'breeding_slot_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_id');
    }

    public function isPending(): bool
    {
        return $this->status === BreedingSlotTransferStatus::Pending;
    }
}
