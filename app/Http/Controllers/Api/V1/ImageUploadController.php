<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadHotelImagesRequest;
use App\Http\Requests\UploadRoomTypeImagesRequest;
use App\Models\Hotel;
use App\Models\RoomType;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles image uploads for hotels and room types
 *
 * All operations require:
 * - Authentication (Sanctum token)
 * - Ownership verification (only hotel owner can upload)
 */
class ImageUploadController extends Controller
{
    public function __construct(
        protected ImageUploadService $imageService
    ) {}

    /**
     * Upload images for a hotel
     *
     * POST /api/v1/manage/hotels/{hotel}/images
     * Content-Type: multipart/form-data
     * Body: images[] = file1.jpg, images[] = file2.jpg, ...
     *
     * WHY multipart/form-data?
     * Regular JSON can't handle binary files (like images).
     * multipart/form-data is the standard for file uploads.
     */
    public function uploadHotelImages(
        UploadHotelImagesRequest $request,
        Hotel $hotel
    ): JsonResponse {
        /*
         * ─── STEP 1: Authorization ───
         * Only the hotel owner can upload images
         */
        if ($hotel->user_id !== $request->user()->id) {
            abort(403, 'You do not own this hotel.');
        }

        /*
         * ─── STEP 2: Upload the images ───
         *
         * $request->file('images') returns an array of UploadedFile objects
         * The service handles resizing, optimizing, and storing them
         */
        $uploadedImages = $this->imageService->uploadMultiple(
            $request->file('images'),
            'hotels'
        );

        /*
         * ─── STEP 3: Merge with existing images ───
         *
         * We APPEND to existing images (not replace).
         * If hotel already has 3 photos and user uploads 2 more,
         * total = 5 photos.
         */
        $existingImages = $hotel->images ?? [];
        $allImages      = array_merge($existingImages, $uploadedImages);

        /*
         * ─── STEP 4: Save to database ───
         */
        $hotel->update(['images' => $allImages]);

        return response()->json([
            'message'         => count($uploadedImages) . ' image(s) uploaded successfully!',
            'uploaded_count'  => count($uploadedImages),
            'total_images'    => count($allImages),
            'data'            => [
                'images' => $hotel->fresh()->images_with_urls,
            ],
        ], 201);
    }

    /**
     * Delete a specific hotel image
     *
     * DELETE /api/v1/manage/hotels/{hotel}/images
     * Body: { "image_index": 0 }  (which image to delete, 0-based)
     */
    public function deleteHotelImage(
        Request $request,
        Hotel $hotel
    ): JsonResponse {
        // Authorization
        if ($hotel->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'image_index' => ['required', 'integer', 'min:0'],
        ]);

        $images = $hotel->images ?? [];
        $index  = $validated['image_index'];

        // Check the index exists
        if (!isset($images[$index])) {
            return response()->json([
                'message' => 'Image not found at that index.',
            ], 404);
        }

        // Delete the file from storage
        $this->imageService->delete($images[$index]);

        // Remove from array
        unset($images[$index]);

        // Reindex the array (0, 1, 2 instead of 0, 2)
        $images = array_values($images);

        // Save
        $hotel->update(['images' => $images]);

        return response()->json([
            'message'      => 'Image deleted successfully.',
            'total_images' => count($images),
            'data'         => [
                'images' => $hotel->fresh()->images_with_urls,
            ],
        ]);
    }

    /**
     * Replace all hotel images (delete old, upload new)
     *
     * PUT /api/v1/manage/hotels/{hotel}/images
     */
    public function replaceHotelImages(
        UploadHotelImagesRequest $request,
        Hotel $hotel
    ): JsonResponse {
        if ($hotel->user_id !== $request->user()->id) {
            abort(403);
        }

        // Delete old images from storage
        if (!empty($hotel->images)) {
            $this->imageService->deleteMultiple($hotel->images);
        }

        // Upload new images
        $uploadedImages = $this->imageService->uploadMultiple(
            $request->file('images'),
            'hotels'
        );

        $hotel->update(['images' => $uploadedImages]);

        return response()->json([
            'message'      => 'All images replaced successfully!',
            'total_images' => count($uploadedImages),
            'data'         => [
                'images' => $hotel->fresh()->images_with_urls,
            ],
        ]);
    }

    /**
     * Upload images for a room type
     *
     * POST /api/v1/manage/room-types/{roomType}/images
     */
    public function uploadRoomTypeImages(
        UploadRoomTypeImagesRequest $request,
        RoomType $roomType
    ): JsonResponse {
        // Authorization - check hotel ownership through room type
        if ($roomType->hotel->user_id !== $request->user()->id) {
            abort(403, 'You do not own this room type.');
        }

        $uploadedImages = $this->imageService->uploadMultiple(
            $request->file('images'),
            'rooms'
        );

        $existingImages = $roomType->images ?? [];
        $allImages      = array_merge($existingImages, $uploadedImages);

        $roomType->update(['images' => $allImages]);

        return response()->json([
            'message'        => count($uploadedImages) . ' image(s) uploaded successfully!',
            'uploaded_count' => count($uploadedImages),
            'total_images'   => count($allImages),
            'data'           => [
                'images' => $roomType->fresh()->images_with_urls,
            ],
        ], 201);
    }

    /**
     * Delete a specific room type image
     *
     * DELETE /api/v1/manage/room-types/{roomType}/images
     */
    public function deleteRoomTypeImage(
        Request $request,
        RoomType $roomType
    ): JsonResponse {
        if ($roomType->hotel->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'image_index' => ['required', 'integer', 'min:0'],
        ]);

        $images = $roomType->images ?? [];
        $index  = $validated['image_index'];

        if (!isset($images[$index])) {
            return response()->json([
                'message' => 'Image not found at that index.',
            ], 404);
        }

        $this->imageService->delete($images[$index]);

        unset($images[$index]);
        $images = array_values($images);

        $roomType->update(['images' => $images]);

        return response()->json([
            'message'      => 'Image deleted successfully.',
            'total_images' => count($images),
            'data'         => [
                'images' => $roomType->fresh()->images_with_urls,
            ],
        ]);
    }

    /**
     * Reorder hotel images
     *
     * PUT /api/v1/manage/hotels/{hotel}/images/reorder
     * Body: { "order": [2, 0, 1, 3] }
     *
     * WHY? Hotel owners want to control which photo appears first
     * (the "cover" image).
     */
    public function reorderHotelImages(
        Request $request,
        Hotel $hotel
    ): JsonResponse {
        if ($hotel->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'min:0'],
        ]);

        $images  = $hotel->images ?? [];
        $newOrder = [];

        // Reorder based on the provided indexes
        foreach ($validated['order'] as $index) {
            if (isset($images[$index])) {
                $newOrder[] = $images[$index];
            }
        }

        $hotel->update(['images' => $newOrder]);

        return response()->json([
            'message' => 'Images reordered successfully.',
            'data'    => [
                'images' => $hotel->fresh()->images_with_urls,
            ],
        ]);
    }
}