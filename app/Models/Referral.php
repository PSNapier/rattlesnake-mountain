<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'recruit_id',
        'referrer_id',
        'granted_at',
        'revoked_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function recruit(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruit_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function isGranted(): bool
    {
        return $this->granted_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }
}
