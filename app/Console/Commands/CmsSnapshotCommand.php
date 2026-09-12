<?php

namespace App\Console\Commands;

use App\Models\CmsPage;
use App\Models\MenuItem;
use App\Support\CmsSnapshot;
use Illuminate\Console\Command;

class CmsSnapshotCommand extends Command
{
    protected $signature = 'cms:snapshot';

    protected $description = 'Capture the current CMS pages and navigation menu to the snapshot fixture';

    /**
     * Columns the seeder replays. `id` and timestamps are deliberately left
     * out so a restore does not fight autoincrement.
     *
     * @var list<string>
     */
    private const PAGE_COLUMNS = [
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

    public function handle(): int
    {
        $snapshot = [
            'pages' => $this->pages(),
            'menu' => $this->menu(null),
        ];

        CmsSnapshot::write($snapshot);

        $this->info(sprintf(
            'Wrote %d pages and %d top-level menu items to %s',
            count($snapshot['pages']),
            count($snapshot['menu']),
            CmsSnapshot::path()
        ));

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function pages(): array
    {
        return CmsPage::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (CmsPage $page) => collect(self::PAGE_COLUMNS)
                ->mapWithKeys(fn (string $column) => [$column => $page->{$column}])
                ->all())
            ->values()
            ->all();
    }

    /**
     * Nested so the seeder can create parents before children.
     *
     * @return list<array<string, mixed>>
     */
    private function menu(?int $parentId): array
    {
        return MenuItem::query()
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $parentId),
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (MenuItem $item) => [
                'label' => $item->label,
                // Page links are written as their `/slug` so the seeder can
                // re-resolve them against whatever ids a fresh database hands out.
                'path' => $item->isPageRow()
                    ? '/'.$item->page()->withTrashed()->value('slug')
                    : $item->path,
                'sort_order' => $item->sort_order,
                'children' => $this->menu($item->id),
            ])
            ->values()
            ->all();
    }
}
