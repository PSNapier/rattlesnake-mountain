<?php

use Illuminate\Support\Facades\Storage;

it('rejects path traversal attempts on avatars route', function () {
    // Double-encode so path stays /avatars/..%2F..%2F.env and hits our route
    $response = $this->get('/avatars/..%252F..%252F.env');

    $response->assertNotFound();
});

it('rejects path traversal attempts on character-images route', function () {
    $response = $this->get('/character-images/..%252F..%252F.env');

    $response->assertNotFound();
});

it('rejects path traversal attempts on horse-images route', function () {
    $response = $this->get('/horse-images/..%252F..%252F.env');

    $response->assertNotFound();
});

it('serves valid avatar file successfully', function () {
    $filename = 'test-avatar-'.uniqid().'.png';
    Storage::disk('public')->put('avatars/'.$filename, 'fake image content');

    try {
        $response = $this->get('/avatars/'.$filename);
        $response->assertSuccessful();
    } finally {
        Storage::disk('public')->delete('avatars/'.$filename);
    }
});
