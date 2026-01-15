<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CharacterImageController;
use App\Http\Controllers\DevPasswordController;
use App\Http\Controllers\HerdController;
use App\Http\Controllers\HorseController;
use App\Models\Herd;
use App\Models\Horse;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('dashboard', [CharacterImageController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Admin
Route::middleware(['auth', 'verified', 'can:access-admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/admin/horses/{horse}/archive', [AdminController::class, 'archive'])->name('admin.horses.archive');
    Route::post('/admin/horses/{horse}/contact', [AdminController::class, 'contact'])->name('admin.horses.contact');
});

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
    Route::get('/users', function () {
        return Inertia::render('Users/Index', [
            'users' => User::select('id', 'name')
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
    return Inertia::render('Users/PublicProfile', [
        'user' => $user,
        'herdCount' => Herd::where('owner_id', $user->id)->count(),
        'horseCount' => Horse::where('owner_id', $user->id)->count(),
    ]);
})->name('users.profile');
Route::get('/u/{user}/herds', [HerdController::class, 'publicIndex'])->name('users.herds');
Route::get('/u/{user}/horses', [HorseController::class, 'publicIndex'])->name('users.horses');
Route::get('/u/{user}/horses/{horse}', [HorseController::class, 'publicShow'])->name('users.horses.show');

Route::get('/dev-password', [DevPasswordController::class, 'show'])->name('dev-password');
Route::post('/dev-password', [DevPasswordController::class, 'authenticate'])->name('dev-password.authenticate');

Route::get('/', function () {
    return Inertia::render('static/Home');
})->name('home');

Route::get('/getting-started', function () {
    return Inertia::render('static/GettingStarted');
})->name('getting_started');

Route::get('/rules', function () {
    return Inertia::render('static/Rules');
})->name('rules');

Route::get('/lore', function () {
    return Inertia::render('static/Lore');
})->name('lore');

Route::get('/character-handbook', function () {
    return Inertia::render('static/CharacterHandbook');
})->name('character_handbook');

Route::get('/stats-leveling', function () {
    return Inertia::render('static/StatsLeveling');
})->name('stats_leveling');

Route::get('/character-upload', function () {
    return Inertia::render('static/CharacterUpload');
})->name('character_upload');

Route::get('/shop', function () {
    return Inertia::render('static/Shop');
})->name('shop');

Route::get('/wildlife', function () {
    return Inertia::render('static/Wildlife');
})->name('wildlife');

Route::get('/lifespans', function () {
    return Inertia::render('static/Lifespans');
})->name('lifespans');

Route::get('/story-progression', function () {
    return Inertia::render('static/StoryProgression');
})->name('story_progression');

Route::get('/claiming-npcs', function () {
    return Inertia::render('static/ClaimingNpcs');
})->name('claiming_npcs');

Route::get('/herd-unity', function () {
    return Inertia::render('static/HerdUnity');
})->name('herd_unity');

Route::get('/breeding-foaling', function () {
    return Inertia::render('static/BreedingFoaling');
})->name('breeding_foaling');

Route::get('/player-vs-player', function () {
    return Inertia::render('static/PlayerVsPlayer');
})->name('player_vs_player');

Route::get('/contact-us', function () {
    return Inertia::render('static/ContactUs');
})->name('contact_us');

Route::get('/privacy-policy', function () {
    return Inertia::render('static/PrivacyPolicy');
})->name('privacy_policy');

Route::fallback(function () {
    return Inertia::render('NotFound');
})->name('not_found');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
