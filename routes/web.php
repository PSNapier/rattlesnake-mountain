<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
