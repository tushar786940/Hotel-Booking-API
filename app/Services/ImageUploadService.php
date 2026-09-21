<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Handles all image uploads with automatic optimization
 *
 * FEATURES:
 * - Resizes large images (max 1920x1080 for full-size)
 * - Generates 300x300 thumbnails
 * - Compresses to reduce file size
 * - Returns URLs for API responses
 * - Deletes old files when replaced
 */
class ImageUploadService
{
    /**
     * Maximum dimensions for full-size images
     */
    const MAX_WIDTH  = 1920;
    const MAX_HEIGHT = 1080;

    /**
     * Thumbnail dimensions
     */
    const THUMB_WIDTH  = 400;
    const THUMB_HEIGHT = 300;

    /**
     * Image quality (0-100, higher = better quality but larger file)
     */
    const QUALITY = 85;

    /**
     * Storage disk to use
     */
    const DISK = 'public';

    /**
     * Upload a single image with automatic optimization
     *
     * @param UploadedFile $file      The uploaded file
     * @param string       $directory Where to store it (e.g., 'hotels', 'rooms')
     * @return array                  ['path' => ..., 'thumbnail' => ..., 'url' => ...]
     */
    public function upload(UploadedFile $file, string $directory): array
    {
        /*
         * ─── STEP 1: Generate unique filename ───
         *
         * WHY unique? Two users might upload "photo.jpg"
         * If we use the original name, the second one overwrites the first!
         *
         * Solution: Random string + extension
         * Example: "photo.jpg" → "a8Kx9mPq2vLwN.jpg"
         */
        $extension = $file->getClientOriginalExtension();
        $filename  = Str::random(40) . '.' . $extension;

        // Full-size path: hotels/a8Kx9mPq2vLwN.jpg
        $path = "{$directory}/{$filename}";

        // Thumbnail path: hotels/thumbnails/a8Kx9mPq2vLwN.jpg
        $thumbnailPath = "{$directory}/thumbnails/{$filename}";

        /*
         * ─── STEP 2: Process full-size image ───
         *
         * We resize large images to save bandwidth.
         * A 6000x4000 photo from a phone is HUGE (10+ MB).
         * We resize it to max 1920x1080 (fits any screen).
         */
        $fullImage = Image::read($file)
            ->scaleDown(
                width: self::MAX_WIDTH,
                height: self::MAX_HEIGHT
            )
            ->toJpeg(self::QUALITY); // Convert to JPEG with 85% quality

        /*
         * ─── STEP 3: Create thumbnail ───
         *
         * WHY thumbnails? For list views (search results),
         * we don't need 1920x1080 images. 400x300 loads 10x faster.
         *
         * cover() = resize AND crop to exact dimensions
         * This ensures all thumbnails are the same size (great for grids)
         */
        $thumbnail = Image::read($file)
            ->cover(self::THUMB_WIDTH, self::THUMB_HEIGHT)
            ->toJpeg(self::QUALITY);

        /*
         * ─── STEP 4: Save both files to storage ───
         */
        Storage::disk(self::DISK)->put($path, (string) $fullImage);
        Storage::disk(self::DISK)->put($thumbnailPath, (string) $thumbnail);

        /*
         * ─── STEP 5: Return metadata ───
         *
         * We return everything the app might need:
         * - path: internal storage path (for deleting later)
         * - thumbnail: thumbnail path
         * - url: full URL (for API responses)
         * - thumbnail_url: thumbnail URL
         */
        return [
            'path'          => $path,
            'thumbnail'     => $thumbnailPath,
            'url'           => Storage::disk(self::DISK)->url($path),
            'thumbnail_url' => Storage::disk(self::DISK)->url($thumbnailPath),
        ];
    }

    /**
     * Upload multiple images at once
     *
     * @param array  $files     Array of UploadedFile objects
     * @param string $directory Where to store them
     * @return array            Array of image metadata
     */
    public function uploadMultiple(array $files, string $directory): array
    {
        $uploaded = [];

        foreach ($files as $file) {
            $uploaded[] = $this->upload($file, $directory);
        }

        return $uploaded;
    }

    /**
     * Delete an image and its thumbnail
     *
     * @param array|string $image The image data (or just the path)
     */
    public function delete(array|string $image): void
    {
        // Handle both formats: array with paths, or just a path string
        if (is_array($image)) {
            $path      = $image['path'] ?? null;
            $thumbnail = $image['thumbnail'] ?? null;
        } else {
            $path      = $image;
            $thumbnail = str_replace('/', '/thumbnails/', $image);
        }

        // Delete full-size image
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }

        // Delete thumbnail
        if ($thumbnail && Storage::disk(self::DISK)->exists($thumbnail)) {
            Storage::disk(self::DISK)->delete($thumbnail);
        }
    }

    /**
     * Delete multiple images
     */
    public function deleteMultiple(array $images): void
    {
        foreach ($images as $image) {
            $this->delete($image);
        }
    }

    /**
     * Get full URL from a stored path
     *
     * WHY? The database stores just "hotels/abc.jpg"
     * The frontend needs "http://localhost:8000/storage/hotels/abc.jpg"
     */
    public function getUrl(string $path): string
    {
        return Storage::disk(self::DISK)->url($path);
    }
}