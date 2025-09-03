<?php

use Illuminate\Support\Facades\Config;

// it('shows password page when dev password is set', function () {
//     Config::set('app.dev_password', 'test123');

//     $response = $this->get('/');

//     $response->assertRedirect('/dev-password');
// });

// it('allows access when correct password is provided', function () {
//     Config::set('app.dev_password', 'test123');

//     $response = $this->post('/dev-password', [
//         'dev_password' => 'test123',
//     ]);

//     $response->assertRedirect('/');
//     $this->assertTrue(session('dev_authenticated'));
// });

// it('denies access when incorrect password is provided', function () {
//     Config::set('app.dev_password', 'test123');

//     $response = $this->post('/dev-password', [
//         'dev_password' => 'wrong',
//     ]);

//     $response->assertRedirect('/dev-password');
//     $response->assertSessionHasErrors('dev_password');
//     $this->assertNull(session('dev_authenticated'));
// });

// it('allows access to all routes after authentication', function () {
//     Config::set('app.dev_password', 'test123');

//     // First authenticate
//     $this->post('/dev-password', [
//         'dev_password' => 'test123',
//     ]);

//     // Then try to access protected routes
//     $response = $this->get('/');
//     $response->assertOk();

//     $response = $this->get('/getting-started');
//     $response->assertOk();
// });

// it('bypasses protection when no dev password is set', function () {
//     Config::set('app.dev_password', null);

//     $response = $this->get('/');

//     $response->assertOk();
//     $response->assertInertia(fn ($page) => $page->component('static/Home'));
// });

// it('redirects from password page when no dev password is set', function () {
//     Config::set('app.dev_password', null);

//     $response = $this->get('/dev-password');

//     $response->assertRedirect('/');
// });
