<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * `body` is sanitized HTML from [041] on. The form requests sanitize on the
 * way in, so nothing here ever sees raw markup.
 */
class Announcement extends Model
{
    use SoftDeletes;

    /**
     * Archive page size on `/news`.
     */
    public const ARCHIVE_PER_PAGE = 10;

    protected $fillable = [
        'title',
        'body',
        'published_at',
        'author_id',
    ];

    /**
     * Publish time is stored as an absolute instant in the app timezone.
     *
     * Eloquent's plain datetime cast keeps whatever offset the incoming string
     * carried, so an admin outside UTC sending "14:00-06:00" would be written
     * as 14:00 and go live six hours early. Normalising on the way in keeps the
     * stored value comparable with `now()` in `scopePublished()`.
     *
     * @return Attribute<?Carbon, ?string>
     */
    protected function publishedAt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value === null ? null : Carbon::parse($value),
            set: fn ($value) => $value === null
                ? null
                : Carbon::parse($value)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
        );
    }

    /**
     * @return BelongsTo<User, Announcement>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Published means it has a publish timestamp that is not in the future.
     *
     * @param  Builder<Announcement>  $query
     * @return Builder<Announcement>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Announcements shown publicly, newest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function publicFeed(int $limit = 3): Collection
    {
        return static::newestPublished()
            ->limit($limit)
            ->get(['id', 'title', 'body', 'published_at'])
            ->map(fn (Announcement $announcement) => $announcement->toPublicArray())
            ->values();
    }

    /**
     * Every published announcement for the `/news` archive, same shape as the
     * feed. Author stays out of both.
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public static function archive(): LengthAwarePaginator
    {
        return static::newestPublished()
            ->paginate(self::ARCHIVE_PER_PAGE, ['id', 'title', 'body', 'published_at'])
            ->withQueryString()
            ->through(fn (Announcement $announcement) => $announcement->toPublicArray());
    }

    /**
     * @return Builder<Announcement>
     */
    private static function newestPublished(): Builder
    {
        return static::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * @return array{id: int, title: string, body: string, published_at: ?string}
     */
    private function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
