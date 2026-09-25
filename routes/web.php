<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Public-disk fallback for environments where `php artisan storage:link`
 * cannot create a symbolic link (commonly local Windows or restricted hosts).
 * When the link exists, the web server serves the file before Laravel runs.
 */
Route::get('/storage/{path}', function (string $path) {
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $segments = explode('/', $path);

    abort_if(
        $path === '' || in_array('..', $segments, true) || str_contains($path, "\0"),
        404
    );

    $disk = Storage::disk('public');

    abort_unless($disk->exists($path), 404);

    return $disk->response($path, null, [
        'Cache-Control' => 'public, max-age=31536000, immutable',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->where('path', '.*')->name('storage.public');
