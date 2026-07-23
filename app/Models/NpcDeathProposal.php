<?php

namespace App\Models;

use App\Enums\NpcDeathProposalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NpcDeathProposal extends Model
{
    protected $fillable = [
        'horse_id',
        'rolled_at',
        'age_months_at_roll',
        'chance_percent',
        'status',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'rolled_at' => 'datetime',
            'resolved_at' => 'datetime',
            'age_months_at_roll' => 'integer',
            'chance_percent' => 'integer',
            'status' => NpcDeathProposalStatus::class,
        ];
    }

    public function horse(): BelongsTo
    {
        return $this->belongsTo(Horse::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isPending(): bool
    {
        return $this->status === NpcDeathProposalStatus::Pending;
    }
}
