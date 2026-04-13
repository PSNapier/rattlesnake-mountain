<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopListing extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'visible_in_shop',
        'scorpion_price',
        'shop_description',
        'image_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'visible_in_shop' => 'boolean',
            'scorpion_price' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
