<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A navbar entry. A page row points at a CMS page and follows its
 * visibility. A ghost row has only a path, for app routes and external links.
 */
class MenuItem extends Model
{
    protected $fillable = [
        'parent_id',
        'cms_page_id',
        'label',
        'path',
        'sort_order',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Excludes soft-deleted pages, so a trashed page's row reads as pageless.
     *
     * @return BelongsTo<CmsPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }

    public function isPageRow(): bool
    {
        return $this->cms_page_id !== null;
    }

    /**
     * A page row always targets its page, whatever `path` still holds from
     * before the link existed.
     */
    public function targetPath(): ?string
    {
        if ($this->isPageRow()) {
            return $this->page ? '/'.$this->page->slug : null;
        }

        return $this->path !== null && $this->path !== '' ? $this->path : null;
    }

    /**
     * Ghost rows, plus page rows whose page still exists.
     *
     * @param  Builder<MenuItem>  $query
     */
    public function scopeWithoutTrashedPages(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereNull('cms_page_id')
            ->orWhereHas('page'));
    }

    /**
     * Ghost rows, plus page rows whose page is live and not deleted.
     *
     * @param  Builder<MenuItem>  $query
     */
    public function scopeShownInHeader(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereNull('cms_page_id')
            ->orWhereHas('page', fn (Builder $page) => $page->live()));
    }
}
