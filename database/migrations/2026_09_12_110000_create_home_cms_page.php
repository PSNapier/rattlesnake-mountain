<?php

use App\Support\CmsSanitizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * [040] The home page becomes the `home` CMS page, carrying what the
 * hardcoded Welcome page showed.
 *
 * The query builder sees soft-deleted rows too, which matters: a trashed row
 * still holds the unique slug. An existing row is never touched, so a rerun
 * cannot clobber an admin's edits.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('cms_pages')->where('slug', 'home')->exists()) {
            return;
        }

        $about = '<h2>Hello there!</h2>'
            .'<p>The battle-born state welcomes you.</p>'
            .'<br />'
            .'<h4>About Us</h4>'
            .'<p>Rattlesnake Mountain is a moderately realistic wild horse art roleplay game founded in the fall of 2019. '
            .'Our setting is the Virginia Range in Nevada, the home of some hardy and sure-footed little horses '
            .'that--while invasive--have made a home of the harsh desert. These horses have fiery souls and '
            .'courageous hearts; but will that be enough for your character to survive alongside them?</p>';

        $boxes = [
            ['id' => 'b1', 'width' => 'two-thirds', 'style' => 'box-centered', 'html' => $about],
            ['id' => 'news', 'width' => 'third', 'style' => 'box-centered', 'kind' => 'news', 'html' => ''],
            [
                'id' => 'b3',
                'width' => 'full',
                'style' => 'box-centered',
                'html' => '<h2>New? Get started <a href="/getting-started">here.</a></h2>',
            ],
            [
                'id' => 'b4',
                'width' => 'half',
                'style' => 'band',
                'html' => $this->linkCard(
                    '@Rattlesnake-Mountain',
                    'https://www.deviantart.com/rattlesnake-mountain',
                    '/images/group-logo.png',
                    'DeviantArt Group logo',
                    'Find our DeviantArt Group.'
                ),
            ],
            [
                'id' => 'b5',
                'width' => 'half',
                'style' => 'band',
                'html' => $this->linkCard(
                    '@discord',
                    'https://discord.gg/rArZNnkCfE',
                    '/images/discord-logo.png',
                    'Discord server logo',
                    'Discord!'
                ),
            ],
        ];

        DB::table('cms_pages')->insert([
            'slug' => 'home',
            'title' => 'Home',
            'description' => null,
            'hero_title' => 'Rattlesnake Mountain',
            'hero_description' => 'An ARPG for wild horse enthusiasts!',
            'content' => json_encode(
                CmsSanitizer::sanitizeBoxes($boxes, 'home'),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
            'coming_soon' => false,
            'visibility' => 'live',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Deliberately empty. Once the migration has run, the row is the admins'
     * page, and rolling back code must not delete their edits with it.
     */
    public function down(): void {}

    /**
     * The markup LandingLinkBox rendered, minus the image classes the
     * sanitizer strips. The band renderer sizes the logo instead.
     */
    private function linkCard(string $title, string $href, string $src, string $alt, string $description): string
    {
        return sprintf(
            '<h3><a href="%s">%s<img src="%s" alt="%s" /></a></h3><p>%s</p>',
            e($href),
            e($title),
            e($src),
            e($alt),
            e($description)
        );
    }
};
