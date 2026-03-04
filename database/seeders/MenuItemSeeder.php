<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MenuItem::whereNotNull('parent_id')->delete();
        MenuItem::whereNull('parent_id')->delete();

        // Top-level items
        $home = MenuItem::create([
            'label' => 'Home',
            'path' => '/',
            'sort_order' => 1,
        ]);

        $gettingStarted = MenuItem::create([
            'label' => 'Getting Started',
            'path' => '/getting-started',
            'sort_order' => 2,
        ]);

        $wildlife = MenuItem::create([
            'label' => 'Wildlife',
            'path' => '/wildlife',
            'sort_order' => 3,
        ]);

        $contactUs = MenuItem::create([
            'label' => 'Contact Us',
            'path' => '/contact-us',
            'sort_order' => 4,
        ]);

        // Getting Started submenu
        $gettingStartedChildren = [
            ['label' => 'Rules', 'path' => '/rules'],
            ['label' => 'Lore', 'path' => '/lore'],
            ['label' => 'Character Handbook', 'path' => '/character-handbook'],
            ['label' => 'Stats & Leveling', 'path' => '/stats-leveling'],
            ['label' => 'Character Upload', 'path' => '/character-upload'],
            ['label' => 'Shop', 'path' => '/shop'],
        ];

        foreach ($gettingStartedChildren as $index => $child) {
            MenuItem::create([
                'parent_id' => $gettingStarted->id,
                'label' => $child['label'],
                'path' => $child['path'],
                'sort_order' => $index + 1,
            ]);
        }

        // Wildlife submenu
        $wildlifeChildren = [
            ['label' => 'Lifespans', 'path' => '/lifespans'],
            ['label' => 'Story Progression', 'path' => '/story-progression'],
            ['label' => 'Claiming NPCs', 'path' => '/claiming-npcs'],
            ['label' => 'Herd Unity', 'path' => '/herd-unity'],
            ['label' => 'Breeding & Foaling', 'path' => '/breeding-foaling'],
            ['label' => 'Player vs. Player', 'path' => '/player-vs-player'],
        ];

        foreach ($wildlifeChildren as $index => $child) {
            MenuItem::create([
                'parent_id' => $wildlife->id,
                'label' => $child['label'],
                'path' => $child['path'],
                'sort_order' => $index + 1,
            ]);
        }
    }
}
