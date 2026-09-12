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

    /**
     * The announcement archive. Linked from home's read-more, so hiding or
     * deleting it would break the front page the same way.
     */
    public const NEWS_SLUG = 'news';

    /**
     * Pages reached without the navbar. They render in their own admin card,
     * carry no menu row and cannot be deleted. `is_system` is set by migration
     * and seeder only, never by an admin.
     *
     * @var list<string>
     */
    public const SYSTEM_SLUGS = [self::HOME_SLUG, 'privacy-policy'];

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
        'is_system' => 'boolean',
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

    /**
     * Pages that can be neither hidden nor deleted.
     */
    public function isProtected(): bool
    {
        return in_array($this->slug, [self::HOME_SLUG, self::NEWS_SLUG], true);
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
