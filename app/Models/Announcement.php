<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Announcement extends Model
{
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
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public static function publicFeed(int $limit = 3): \Illuminate\Support\Collection
    {
        return static::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'title', 'body', 'published_at'])
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'published_at' => $announcement->published_at?->toIso8601String(),
            ])
            ->values();
    }
}
