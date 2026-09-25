<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Public-disk fallback for environments where `php artisan storage:link`
 * cannot create a symbolic link (commonly local Windows or restricted hosts).
 * When the link exists, the web server serves the file before Laravel runs.
 *
 * Older installations may have written Filament uploads to the default local
 * disk. For known public image directories, migrate those files to the public
 * disk on first request so existing database paths continue to work.
 */
Route::get('/storage/{path}', function (string $path) {
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $segments = explode('/', $path);

    abort_if(
        $path === '' || in_array('..', $segments, true) || str_contains($path, "\0"),
        404
    );

    $headers = [
        'Cache-Control' => 'public, max-age=31536000, immutable',
        'X-Content-Type-Options' => 'nosniff',
    ];

    $publicDisk = Storage::disk('public');

    if ($publicDisk->exists($path)) {
        return $publicDisk->response($path, null, $headers);
    }

    $isPublicImagePath = str_starts_with($path, 'hotels/')
        || str_starts_with($path, 'rooms/');

    abort_unless($isPublicImagePath, 404);

    $serveOrMigrate = function (FilesystemAdapter $sourceDisk) use (
        $headers,
        $path,
        $publicDisk
    ) {
        if (! $sourceDisk->exists($path)) {
            return null;
        }

        $stream = $sourceDisk->readStream($path);

        if (is_resource($stream)) {
            try {
                $publicDisk->put($path, $stream);
            } finally {
                fclose($stream);
            }
        }

        if ($publicDisk->exists($path)) {
            return $publicDisk->response($path, null, $headers);
        }

        return $sourceDisk->response($path, null, $headers);
    };

    // Current Laravel local disk root: storage/app/private.
    if ($response = $serveOrMigrate(Storage::disk('local'))) {
        return $response;
    }

    // Legacy Laravel local disk root: storage/app.
    $legacyPath = storage_path("app/{$path}");
    $legacyRealPath = realpath($legacyPath);
    $storageRoot = realpath(storage_path('app'));

    if (
        $legacyRealPath !== false
        && $storageRoot !== false
        && str_starts_with($legacyRealPath, $storageRoot . DIRECTORY_SEPARATOR)
        && is_file($legacyRealPath)
    ) {
        $stream = fopen($legacyRealPath, 'rb');

        if (is_resource($stream)) {
            try {
                $publicDisk->put($path, $stream);
            } finally {
                fclose($stream);
            }
        }

        if ($publicDisk->exists($path)) {
            return $publicDisk->response($path, null, $headers);
        }

        return response()->file($legacyRealPath, $headers);
    }

    abort(404, 'Image file not found on the public or legacy upload disks.');
})->where('path', '.*')->name('storage.public');
