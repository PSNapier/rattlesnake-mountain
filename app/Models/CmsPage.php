<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'description',
        'hero_title',
        'hero_description',
        'content',
        'images',
        'coming_soon',
        'sort_order',
    ];

    protected $casts = [
        'content' => 'array',
        'images' => 'array',
        'coming_soon' => 'boolean',
    ];
}
