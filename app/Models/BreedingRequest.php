<?php

namespace App\Models;

use App\Enums\BreedingRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BreedingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'sire_id',
        'dam_id',
        'sire_slot_id',
        'dam_slot_id',
        'evidence_url',
        'notes',
        'status',
        'result_options',
        'selected_option_index',
        'resolved_by_id',
        'rolled_at',
        'resolved_at',
        'foal_id',
        'idempotency_key',
        'genetics_provider',
        'provider_request_id',
        'provider_metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => BreedingRequestStatus::class,
            'result_options' => 'array',
            'selected_option_index' => 'integer',
            'rolled_at' => 'datetime',
            'resolved_at' => 'datetime',
            'provider_metadata' => 'array',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(Horse::class, 'sire_id');
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(Horse::class, 'dam_id');
    }

    public function sireSlot(): BelongsTo
    {
        return $this->belongsTo(BreedingSlot::class, 'sire_slot_id');
    }

    public function damSlot(): BelongsTo
    {
        return $this->belongsTo(BreedingSlot::class, 'dam_slot_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function foal(): BelongsTo
    {
        return $this->belongsTo(Horse::class, 'foal_id');
    }
}
