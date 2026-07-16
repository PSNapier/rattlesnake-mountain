<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'max_count',
        'uses_per_unit',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_count' => 'integer',
            'uses_per_unit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function shopListing(): HasOne
    {
        return $this->hasOne(ShopListing::class);
    }
}
