<?php

use App\Models\User;
use Database\Seeders\CmsPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

/**
 * @return list<string>
 */
function frontendSourceFiles(): array
{
    $files = [];

    // Blade is included because the published paginator views ship dark:
    // variants of their own, and the app renders them on every paginated page.
    foreach ([resource_path('js'), resource_path('views'), resource_path('css')] as $root) {
        $directory = new RecursiveDirectoryIterator($root);

        foreach (new RecursiveIteratorIterator($directory) as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['vue', 'ts', 'php', 'css'], true)) {
                $files[] = $file->getPathname();
            }
        }
    }

    sort($files);

    return $files;
}

it('renders key pages without a theme toggle', function () {
    $this->seed(CmsPageSeeder::class);

    // No source file may ship a dark-mode utility. Tailwind's built-in `dark:`
    // variant falls back to prefers-color-scheme, so these are not inert: a
    // player whose OS is set to dark sees them.
    $offenders = [];

    foreach (frontendSourceFiles() as $path) {
        $contents = file_get_contents($path);
        $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);

        if (preg_match('/(?<![\w-])dark:/', $contents)) {
            $offenders[] = $relative.' (dark: utility)';
        }

        if (str_contains($contents, 'useAppearance') || str_contains($contents, 'initializeTheme')) {
            $offenders[] = $relative.' (theme switching)';
        }
    }

    expect($offenders)->toBe([]);

    // The theme-switch machinery itself is gone, not merely unreferenced.
    expect(file_exists(resource_path('js/composables/useAppearance.ts')))->toBeFalse();
    expect(file_exists(resource_path('js/components/AppearanceTabs.vue')))->toBeFalse();
    expect(file_exists(app_path('Http/Middleware/HandleAppearance.php')))->toBeFalse();
    expect(file_get_contents(base_path('bootstrap/app.php')))->not->toContain('HandleAppearance');

    // Tailwind must not be pointed at sources that reintroduce the variant.
    $css = file_get_contents(resource_path('css/app.css'));
    expect($css)->not->toContain('@custom-variant dark');
    expect($css)->not->toContain('storage/framework/views');
    expect($css)->not->toContain('Illuminate/Pagination');

    // And the pages still render.
    $user = User::factory()->create();

    foreach (['/', '/rules', '/getting-started', '/shop', '/login'] as $path) {
        $this->get($path)->assertSuccessful();
    }

    foreach (['/leaderboard', '/u/'.$user->id] as $path) {
        $this->get($path)->assertSuccessful();
    }

    foreach ([
        '/dashboard',
        '/inventory',
        '/trades',
        '/users',
        '/settings/profile',
        '/settings/appearance',
    ] as $path) {
        actingAs($user)->get($path)->assertSuccessful();
    }

    // The admin dashboard carried its own stripped classes.
    $admin = User::factory()->create(['role' => \App\Models\Role::Admin]);
    actingAs($admin)->get('/admin')->assertSuccessful();
});

it('omits obsolete toyhouse and admin account links from home', function () {
    $home = file_get_contents(resource_path('js/pages/Welcome.vue'));

    expect($home)->not->toContain('rattlesnakeadmin');
    expect($home)->not->toContain('toyhou.se');
    expect(strtolower($home))->not->toContain('toyhouse');

    // The DeviantArt group and Discord are current, so they stay.
    expect($home)->toContain('deviantart.com/rattlesnake-mountain');
    expect($home)->toContain('discord.gg');

    // No page may still promote the import account. pages/Home.vue was an
    // unrouted duplicate of Welcome that still carried the box; deleting it is
    // why nothing references the logo any more.
    $promoters = [];

    foreach (frontendSourceFiles() as $path) {
        if (! str_contains($path, DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        if (str_contains(file_get_contents($path), 'admin-logo')) {
            $promoters[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
        }
    }

    expect($promoters)->toBe([]);

    $this->get('/')->assertSuccessful();
});
