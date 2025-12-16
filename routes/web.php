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
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware(['auth', 'verified', 'can:access-admin'])
    ->name('admin.index');

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

// Character Image Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/character-images', [CharacterImageController::class, 'store'])->name('character-images.store')->middleware('rate.limit.uploads');
    Route::delete('/character-images/{characterImage}', [CharacterImageController::class, 'destroy'])->name('character-images.destroy');
});

// Herd and Horse Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('herds', HerdController::class);
    Route::resource('horses', HorseController::class);
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
