<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsPage extends Model
{
    use SoftDeletes;

    public const VISIBILITY_LIVE = 'live';

    public const VISIBILITY_HIDDEN = 'hidden';

    /**
     * The one page that cannot be hidden or deleted: hiding it would take the
     * site's front door down.
     */
    public const HOME_SLUG = 'home';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'hero_title',
        'hero_description',
        'content',
        'coming_soon',
        'visibility',
        'sort_order',
    ];

    protected $casts = [
        'content' => 'array',
        'coming_soon' => 'boolean',
    ];

    protected $attributes = [
        'visibility' => self::VISIBILITY_HIDDEN,
    ];

    /**
     * Newest first. Ten saves in the same second are plausible, so the id
     * breaks the tie rather than leaving the order to the database.
     *
     * @return HasMany<CmsPageRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(CmsPageRevision::class)
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function isHome(): bool
    {
        return $this->slug === self::HOME_SLUG;
    }

    public function isLive(): bool
    {
        return $this->visibility === self::VISIBILITY_LIVE;
    }

    /**
     * @param  Builder<CmsPage>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('visibility', self::VISIBILITY_LIVE);
    }
}
