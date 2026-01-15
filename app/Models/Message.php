<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'horse_id',
        'user_id',
        'admin_id',
        'subject',
        'initial_message',
        'is_read',
        'read_at',
        'status',
        'responded_at',
        'admin_edits',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'responded_at' => 'datetime',
            'admin_edits' => 'array',
        ];
    }

    public function horse(): BelongsTo
    {
        return $this->belongsTo(Horse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MessageComment::class)->orderBy('created_at');
    }
}
