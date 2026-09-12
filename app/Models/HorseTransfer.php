<?php

namespace App\Models;

use App\Enums\HorseTransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorseTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'horse_id',
        'from_user_id',
        'to_user_id',
        'acted_by_id',
        'status',
        'notes',
        'reason',
        'resolved_at',
    ];

    // `pending_horse_id` is deliberately absent from $fillable: it is a generated
    // column backing the one-pending-transfer-per-horse unique index, and the
    // database owns it.

    protected function casts(): array
    {
        return [
            'status' => HorseTransferStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function horse(): BelongsTo
    {
        return $this->belongsTo(Horse::class);
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
        return $this->status === HorseTransferStatus::Pending;
    }
}
