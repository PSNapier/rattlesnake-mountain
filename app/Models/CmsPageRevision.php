<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A snapshot of a CMS page as it stood before a save.
 *
 * Written only by CmsController: inline editing is the only thing that makes
 * revisions, and the ten newest per page are kept.
 */
class CmsPageRevision extends Model
{
    /**
     * How many snapshots survive per page.
     */
    public const KEEP = 10;

    protected $fillable = [
        'cms_page_id',
        'user_id',
        'title',
        'description',
        'hero_title',
        'hero_description',
        'content',
        'coming_soon',
    ];

    protected $casts = [
        'content' => 'array',
        'coming_soon' => 'boolean',
    ];

    /**
     * @return BelongsTo<CmsPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
