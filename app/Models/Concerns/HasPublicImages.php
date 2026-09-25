<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasPublicImages
{
    /**
     * Return image metadata with browser-accessible URLs.
     *
     * Filament stores public FileUpload values as path strings, while the
     * image upload API stores arrays containing full-size and thumbnail paths.
     * This normalizes both formats without corrupting already-absolute URLs.
     *
     * @return array<int, array{url: string, thumbnail_url: string}>
     */
    public function getImagesWithUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn (mixed $image): ?array => $this->normalizePublicImage($image))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Get the first image suitable for hotel and room cards.
     */
    public function getCoverImageAttribute(): ?string
    {
        $firstImage = $this->images_with_urls[0] ?? null;

        return $firstImage['thumbnail_url'] ?? $firstImage['url'] ?? null;
    }

    /**
     * A flat URL list is useful for frontends that do not need thumbnails.
     *
     * @return array<int, string>
     */
    public function getImageUrlsAttribute(): array
    {
        return collect($this->images_with_urls)
            ->pluck('url')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array{url: string, thumbnail_url: string}|null
     */
    protected function normalizePublicImage(mixed $image): ?array
    {
        if (is_string($image)) {
            $url = $this->publicImageUrl($image);

            return $url === null
                ? null
                : ['url' => $url, 'thumbnail_url' => $url];
        }

        if (! is_array($image)) {
            return null;
        }

        $path = $image['path'] ?? $image['url'] ?? null;
        $thumbnail = $image['thumbnail'] ?? $image['thumbnail_url'] ?? $path;
        $url = $this->publicImageUrl($path);
        $thumbnailUrl = $this->publicImageUrl($thumbnail) ?? $url;

        if ($url === null && $thumbnailUrl === null) {
            return null;
        }

        return [
            'url' => $url ?? $thumbnailUrl,
            'thumbnail_url' => $thumbnailUrl ?? $url,
        ];
    }

    protected function publicImageUrl(mixed $path): ?string
    {
        if (! is_string($path) || blank($path)) {
            return null;
        }

        $path = trim($path);

        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:', 'blob:'])) {
            return $path;
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#^(?:/?storage/|/?public/)#', '', $path) ?? $path;
        $path = ltrim($path, '/');

        return $path === '' ? null : asset("storage/{$path}");
    }
}
