<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\MenuItem;
use App\Support\CmsSnapshot;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Non-destructive: an item that already exists at a given label and parent
     * is left alone, so reseeding for unrelated data never rewrites navigation
     * an admin has edited.
     */
    public function run(): void
    {
        $this->createTree($this->menu(), null);
    }

    /**
     * The snapshot fixture when one has been captured, otherwise the tree
     * hardcoded below.
     *
     * @return list<array<string, mixed>>
     */
    private function menu(): array
    {
        $snapshot = CmsSnapshot::read();

        if ($snapshot !== null && $snapshot['menu'] !== []) {
            return $snapshot['menu'];
        }

        return $this->hardcodedMenu();
    }

    /**
     * Parents are created before their children, which is why the tree is
     * stored nested rather than flat.
     *
     * @param  list<array<string, mixed>>  $items
     */
    private function createTree(array $items, ?int $parentId): void
    {
        foreach ($items as $index => $item) {
            $page = $this->pageAt($item['path'] ?? null);

            // System pages are reached without the navbar and carry no row.
            if ($page?->is_system) {
                continue;
            }

            $linkedPageId = $page !== null && ! MenuItem::query()->where('cms_page_id', $page->id)->exists()
                ? $page->id
                : null;

            $node = MenuItem::query()->firstOrCreate(
                [
                    'label' => $item['label'],
                    'parent_id' => $parentId,
                ],
                [
                    'path' => $item['path'] ?? null,
                    'sort_order' => $item['sort_order'] ?? $index + 1,
                    'cms_page_id' => $linkedPageId,
                ]
            );

            if ($linkedPageId !== null && $node->cms_page_id === null) {
                $node->update(['cms_page_id' => $linkedPageId]);
            }

            $this->createTree(array_values($item['children'] ?? []), $node->id);
        }
    }

    /**
     * Snapshots store page links as `/slug` paths, including ones captured
     * before `cms_page_id` existed, so the link is re-resolved on every replay.
     */
    private function pageAt(?string $path): ?CmsPage
    {
        if ($path === null || ! preg_match('#^/([^/?\#]+)$#', $path, $matches)) {
            return null;
        }

        return CmsPage::withTrashed()->where('slug', $matches[1])->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function hardcodedMenu(): array
    {
        return [
            ['label' => 'Home', 'path' => '/', 'sort_order' => 1],
            [
                'label' => 'Getting Started',
                'path' => '/getting-started',
                'sort_order' => 2,
                'children' => [
                    ['label' => 'Rules', 'path' => '/rules', 'sort_order' => 1],
                    ['label' => 'Lore', 'path' => '/lore', 'sort_order' => 2],
                    ['label' => 'Character Handbook', 'path' => '/character-handbook', 'sort_order' => 3],
                    ['label' => 'Stats & Leveling', 'path' => '/stats-leveling', 'sort_order' => 4],
                    ['label' => 'Character Upload', 'path' => '/character-upload', 'sort_order' => 5],
                    ['label' => 'Shop', 'path' => '/shop', 'sort_order' => 6],
                ],
            ],
            [
                'label' => 'Wildlife',
                'path' => '/wildlife',
                'sort_order' => 3,
                'children' => [
                    ['label' => 'Lifespans', 'path' => '/lifespans', 'sort_order' => 1],
                    ['label' => 'Story Progression', 'path' => '/story-progression', 'sort_order' => 2],
                    ['label' => 'Claiming NPCs', 'path' => '/claiming-npcs', 'sort_order' => 3],
                    ['label' => 'Herd Unity', 'path' => '/herd-unity', 'sort_order' => 4],
                    ['label' => 'Breeding & Foaling', 'path' => '/breeding-foaling', 'sort_order' => 5],
                    ['label' => 'Player vs. Player', 'path' => '/player-vs-player', 'sort_order' => 6],
                ],
            ],
            ['label' => 'Contact Us', 'path' => '/contact-us', 'sort_order' => 4],
        ];
    }
}
