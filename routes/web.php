<?php

use App\Http\Controllers\Admin\CmsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\LifecycleController;
use App\Http\Controllers\Admin\ShopListingController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AdminRollerController;
use App\Http\Controllers\CharacterImageController;
use App\Http\Controllers\DevPasswordController;
use App\Http\Controllers\HerdController;
use App\Http\Controllers\HorseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StaticPageController;
use App\Models\Herd;
use App\Models\Horse;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('dashboard', function () {
    $user = auth()->user();

    return Inertia::render('Users/Index', [
        'user' => $user->only(['id', 'name', 'bio', 'avatar']),
        'herdCount' => Herd::where('owner_id', $user->id)->count(),
        'horseCount' => Horse::where('owner_id', $user->id)->count(),
        'itemCount' => $user->items()->where('is_active', true)->count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Admin
Route::middleware(['auth', 'verified', 'can:access-admin'])->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.index');
    Route::put('/admin/lifecycle', [LifecycleController::class, 'updateLifecycle'])->name('admin.lifecycle.update');
    Route::post('/admin/rollers/horse-randomizer/roll', [AdminRollerController::class, 'rollHorse'])->name('admin.rollers.horse-randomizer.roll');
    Route::post('/admin/horses/{horse}/archive', [SubmissionController::class, 'archive'])->name('admin.horses.archive');
    Route::post('/admin/horses/{horse}/unarchive', [SubmissionController::class, 'unarchive'])->name('admin.horses.unarchive');
    Route::post('/admin/horses/{horse}/contact', [SubmissionController::class, 'contact'])->name('admin.horses.contact');

    // Item Management
    Route::get('/admin/items', [ItemController::class, 'items'])->name('admin.items');
    Route::post('/admin/items', [ItemController::class, 'storeItem'])->name('admin.items.store');
    Route::put('/admin/items/{item}', [ItemController::class, 'updateItem'])->name('admin.items.update');
    Route::delete('/admin/items/{item}', [ItemController::class, 'destroyItem'])->name('admin.items.destroy');

    // Shop Listing Management
    Route::post('/admin/shop-listings', [ShopListingController::class, 'store'])->name('admin.shop-listings.store');
    Route::put('/admin/shop-listings/{shopListing}', [ShopListingController::class, 'update'])->name('admin.shop-listings.update');
    Route::delete('/admin/shop-listings/{shopListing}', [ShopListingController::class, 'destroy'])->name('admin.shop-listings.destroy');

    // User Management
    Route::post('/admin/users/{user}/freeze', [UserController::class, 'freezeUser'])->name('admin.users.freeze');
    Route::post('/admin/users/{user}/unfreeze', [UserController::class, 'unfreezeUser'])->name('admin.users.unfreeze');
    Route::post('/admin/users/{user}/ban', [UserController::class, 'banUser'])->name('admin.users.ban');
    Route::post('/admin/users/{user}/unban', [UserController::class, 'unbanUser'])->name('admin.users.unban');
    Route::put('/admin/users/{user}/role', [UserController::class, 'updateUserRole'])->name('admin.users.role.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'deleteUser'])->name('admin.users.destroy');

    // User Item Management
    Route::get('/admin/users/search', [UserController::class, 'searchUsers'])->name('admin.users.search');
    Route::get('/admin/users/{user}/inventory', [UserController::class, 'getUserInventory'])->name('admin.users.inventory');
    Route::get('/admin/users/{user}/items', [UserController::class, 'userItems'])->name('admin.users.items');
    Route::put('/admin/users/{user}/items', [UserController::class, 'updateUserItem'])->name('admin.users.items.update');

    // CMS
    Route::post('/admin/cms/pages', [CmsController::class, 'storeCmsPage'])->name('admin.cms.pages.store');
    Route::put('/admin/cms/pages/{page}', [CmsController::class, 'updateCmsPage'])->name('admin.cms.pages.update');
    Route::post('/admin/cms/pages/reorder', [CmsController::class, 'reorderCmsPages'])->name('admin.cms.pages.reorder');
    Route::post('/admin/cms/menu', [CmsController::class, 'storeMenuItem'])->name('admin.cms.menu.store');
    Route::post('/admin/cms/menu/reorder', [CmsController::class, 'reorderMenuItems'])->name('admin.cms.menu.reorder');
    Route::put('/admin/cms/menu/{menuItem}', [CmsController::class, 'updateMenuItem'])->name('admin.cms.menu.update');
    Route::delete('/admin/cms/menu/{menuItem}', [CmsController::class, 'destroyMenuItem'])->name('admin.cms.menu.destroy');
});

$servePublicFile = function (string $subdir, string $filename) {
    $filename = basename($filename);
    $baseDir = realpath(storage_path('app/public/'.$subdir));
    if ($baseDir === false) {
        abort(404);
    }
    $path = $baseDir.DIRECTORY_SEPARATOR.$filename;

    if (! file_exists($path)) {
        abort(404);
    }

    $resolved = realpath($path);
    if ($resolved === false || ! str_starts_with($resolved, $baseDir)) {
        abort(404);
    }

    return response(file_get_contents($resolved), 200, [
        'Content-Type' => mime_content_type($resolved),
        'Cache-Control' => 'public, max-age=31536000',
    ]);
};

// Route to serve avatars (must come before other avatar routes)
Route::get('/avatars/{filename}', fn (string $filename) => $servePublicFile('avatars', $filename))->name('avatars.serve');

// Route to serve character images (must come before other character-image routes)
Route::get('/character-images/{filename}', fn (string $filename) => $servePublicFile('character-images', $filename))->name('character-images.serve');

// Route to serve horse images (must come before other horse-image routes)
Route::get('/horse-images/{filename}', fn (string $filename) => $servePublicFile('horse-images', $filename))->name('horse-images.serve');

// Character Image Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/character-images', [CharacterImageController::class, 'store'])->name('character-images.store')->middleware('rate.limit.uploads');
    Route::delete('/character-images/{characterImage}', [CharacterImageController::class, 'destroy'])->name('character-images.destroy');
});

// Herd and Horse Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('herds', HerdController::class);
    Route::resource('horses', HorseController::class);
    Route::post('/horses/{horse}/approve', [HorseController::class, 'approve'])->name('horses.approve');
    Route::post('/horses/{horse}/publish', [HorseController::class, 'publish'])->name('horses.publish');
    Route::post('/horses/upload-image', [HorseController::class, 'uploadImage'])->name('horses.upload-image')->middleware('rate.limit.uploads');
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/users', function () {
        return Inertia::render('Users/List', [
            'users' => User::select('id', 'name', 'avatar')
                ->orderBy('name')
                ->get(),
        ]);
    })->name('users.index');

    // Inbox Routes
    Route::get('/inbox', [\App\Http\Controllers\InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/{message}', [\App\Http\Controllers\InboxController::class, 'show'])->name('inbox.show');
    Route::post('/inbox/{message}/comments', [\App\Http\Controllers\InboxController::class, 'storeComment'])->name('inbox.comments.store');
    Route::post('/inbox/{message}/accept', [\App\Http\Controllers\InboxController::class, 'accept'])->name('inbox.accept');
    Route::post('/inbox/{message}/decline', [\App\Http\Controllers\InboxController::class, 'decline'])->name('inbox.decline');
});

// Public viewing routes for users' herds and horses
Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');

Route::get('/u/{user}', function (User $user) {
    return Inertia::render('Users/Index', [
        'user' => $user->only(['id', 'name', 'bio', 'avatar']),
        'herdCount' => Herd::where('owner_id', $user->id)->count(),
        'horseCount' => Horse::where('owner_id', $user->id)->count(),
        'itemCount' => $user->items()->where('is_active', true)->count() ?: 0,
    ]);
})->name('users.profile');
Route::get('/u/{user}/herds', [HerdController::class, 'publicIndex'])->name('users.herds');
Route::get('/u/{user}/horses', [HorseController::class, 'publicIndex'])->name('users.horses');
Route::get('/u/{user}/horses/{horse}', [HorseController::class, 'publicShow'])->name('users.horses.show');
Route::get('/u/{user}/inventory', [InventoryController::class, 'publicIndex'])->name('users.inventory');

Route::get('/dev-password', [DevPasswordController::class, 'show'])->name('dev-password');
Route::post('/dev-password', [DevPasswordController::class, 'authenticate'])->name('dev-password.authenticate');

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

Route::get('/getting-started', [StaticPageController::class, 'show'])
    ->defaults('slug', 'getting-started')
    ->name('getting_started');

Route::get('/rules', [StaticPageController::class, 'show'])
    ->defaults('slug', 'rules')
    ->name('rules');

Route::get('/lore', [StaticPageController::class, 'show'])
    ->defaults('slug', 'lore')
    ->name('lore');

Route::get('/character-handbook', [StaticPageController::class, 'show'])
    ->defaults('slug', 'character-handbook')
    ->name('character_handbook');

Route::get('/stats-leveling', [StaticPageController::class, 'show'])
    ->defaults('slug', 'stats-leveling')
    ->name('stats_leveling');

Route::get('/character-upload', [StaticPageController::class, 'show'])
    ->defaults('slug', 'character-upload')
    ->name('character_upload');

Route::get('/shop', [ShopController::class, 'index'])->name('shop');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/shop/{shopListing}/purchase', [ShopController::class, 'purchase'])->name('shop.purchase');
});

Route::get('/wildlife', [StaticPageController::class, 'show'])
    ->defaults('slug', 'wildlife')
    ->name('wildlife');

Route::get('/lifespans', [StaticPageController::class, 'show'])
    ->defaults('slug', 'lifespans')
    ->name('lifespans');

Route::get('/story-progression', [StaticPageController::class, 'show'])
    ->defaults('slug', 'story-progression')
    ->name('story_progression');

Route::get('/claiming-npcs', [StaticPageController::class, 'show'])
    ->defaults('slug', 'claiming-npcs')
    ->name('claiming_npcs');

Route::get('/herd-unity', [StaticPageController::class, 'show'])
    ->defaults('slug', 'herd-unity')
    ->name('herd_unity');

Route::get('/breeding-foaling', [StaticPageController::class, 'show'])
    ->defaults('slug', 'breeding-foaling')
    ->name('breeding_foaling');

Route::get('/player-vs-player', [StaticPageController::class, 'show'])
    ->defaults('slug', 'player-vs-player')
    ->name('player_vs_player');

Route::get('/contact-us', [StaticPageController::class, 'show'])
    ->defaults('slug', 'contact-us')
    ->name('contact_us');

Route::get('/privacy-policy', [StaticPageController::class, 'show'])
    ->defaults('slug', 'privacy-policy')
    ->name('privacy_policy');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

Route::get('/{slug}', [StaticPageController::class, 'show'])
    ->where('slug', '[a-zA-Z0-9\-_]+')
    ->name('cms.show');

Route::fallback(function () {
    return Inertia::render('NotFound');
})->name('not_found');
