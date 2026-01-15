<?php

namespace App\Models;

use App\Enums\AdminAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSubmissionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'horse_id',
        'admin_id',
        'action',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'action' => AdminAction::class,
        ];
    }

    public function horse(): BelongsTo
    {
        return $this->belongsTo(Horse::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
