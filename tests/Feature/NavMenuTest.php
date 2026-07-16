<?php

use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shares nav menu with inertia response', function () {
    MenuItem::create([
        'label' => 'Home',
        'path' => '/',
        'sort_order' => 1,
    ]);

    $response = $this->get('/login');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('navMenu')
        ->where('navMenu.0.label', 'Home')
        ->where('navMenu.0.path', '/'));
});

it('includes nested children in nav menu', function () {
    $parent = MenuItem::create([
        'label' => 'Getting Started',
        'path' => '/getting-started',
        'sort_order' => 1,
    ]);
    MenuItem::create([
        'parent_id' => $parent->id,
        'label' => 'Rules',
        'path' => '/rules',
        'sort_order' => 1,
    ]);

    $response = $this->get('/login');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('navMenu.0.children')
        ->where('navMenu.0.children.0.label', 'Rules'));
});
