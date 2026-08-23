<?php

namespace App\Models;

use App\Enums\TradeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'acted_by_id',
        'status',
        'note',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TradeStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<TradeItem, Trade>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TradeItem::class);
    }

    /**
     * @return BelongsTo<User, Trade>
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * @return BelongsTo<User, Trade>
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * @return BelongsTo<User, Trade>
     */
    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_id');
    }

    public function isPending(): bool
    {
        return $this->status === TradeStatus::Pending;
    }

    public function involves(User $user): bool
    {
        return $user->id === $this->from_user_id || $user->id === $this->to_user_id;
    }
}
