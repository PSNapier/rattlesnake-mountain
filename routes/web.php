<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CharacterImageController;
use App\Http\Controllers\DevPasswordController;
use App\Http\Controllers\HerdController;
use App\Http\Controllers\HorseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\StaticPageController;
use App\Models\Herd;
use App\Models\Horse;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('dashboard', function () {
    $user = auth()->user();

    return Inertia::render('users/Index', [
        'user' => $user->only(['id', 'name', 'bio', 'avatar']),
        'herdCount' => Herd::where('owner_id', $user->id)->count(),
        'horseCount' => Horse::where('owner_id', $user->id)->count(),
        'itemCount' => $user->items()->where('is_active', true)->count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Admin
Route::middleware(['auth', 'verified', 'can:access-admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/admin/horses/{horse}/archive', [AdminController::class, 'archive'])->name('admin.horses.archive');
    Route::post('/admin/horses/{horse}/contact', [AdminController::class, 'contact'])->name('admin.horses.contact');

    // Item Management
    Route::get('/admin/items', [AdminController::class, 'items'])->name('admin.items');
    Route::post('/admin/items', [AdminController::class, 'storeItem'])->name('admin.items.store');
    Route::put('/admin/items/{item}', [AdminController::class, 'updateItem'])->name('admin.items.update');
    Route::delete('/admin/items/{item}', [AdminController::class, 'destroyItem'])->name('admin.items.destroy');

    // User Item Management
    Route::get('/admin/users/search', [AdminController::class, 'searchUsers'])->name('admin.users.search');
    Route::get('/admin/users/{user}/inventory', [AdminController::class, 'getUserInventory'])->name('admin.users.inventory');
    Route::get('/admin/users/{user}/items', [AdminController::class, 'userItems'])->name('admin.users.items');
    Route::put('/admin/users/{user}/items', [AdminController::class, 'updateUserItem'])->name('admin.users.items.update');

    // CMS
    Route::put('/admin/cms/pages/{page}', [AdminController::class, 'updateCmsPage'])->name('admin.cms.pages.update');
    Route::post('/admin/cms/pages/reorder', [AdminController::class, 'reorderCmsPages'])->name('admin.cms.pages.reorder');
    Route::post('/admin/cms/menu', [AdminController::class, 'storeMenuItem'])->name('admin.cms.menu.store');
    Route::post('/admin/cms/menu/reorder', [AdminController::class, 'reorderMenuItems'])->name('admin.cms.menu.reorder');
    Route::put('/admin/cms/menu/{menuItem}', [AdminController::class, 'updateMenuItem'])->name('admin.cms.menu.update');
    Route::delete('/admin/cms/menu/{menuItem}', [AdminController::class, 'destroyMenuItem'])->name('admin.cms.menu.destroy');
});

// Route to serve avatars (must come before other avatar routes)
Route::get('/avatars/{filename}', function ($filename) {
    $path = storage_path('app/public/avatars/'.$filename);

    if (! file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return response($file, 200, [
        'Content-Type' => $type,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->name('avatars.serve');

// Route to serve character images (must come before other character-image routes)
Route::get('/character-images/{filename}', function ($filename) {
    $path = storage_path('app/public/character-images/'.$filename);

    if (! file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return response($file, 200, [
        'Content-Type' => $type,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->name('character-images.serve');

// Route to serve horse images (must come before other horse-image routes)
Route::get('/horse-images/{filename}', function ($filename) {
    $path = storage_path('app/public/horse-images/'.$filename);

    if (! file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return response($file, 200, [
        'Content-Type' => $type,
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->name('horse-images.serve');

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
        return Inertia::render('users/List', [
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
Route::get('/u/{user}', function (User $user) {
    return Inertia::render('users/Index', [
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

Route::get('/shop', [StaticPageController::class, 'show'])
    ->defaults('slug', 'shop')
    ->name('shop');

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

Route::fallback(function () {
    return Inertia::render('NotFound');
})->name('not_found');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
