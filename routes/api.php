<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HotelController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\RoomTypeController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\V1\Admin\AdminHotelController;
use App\Http\Controllers\Api\V1\Admin\AdminBookingController;
use App\Http\Controllers\Api\V1\ImageUploadController;
use App\Http\Controllers\Api\V1\InvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| prefix('v1') means all routes start with /api/v1/
| This allows us to create v2 later without breaking v1
*/

Route::prefix('v1')->group(function () {

    // ═══════════════════════════════════════════
    // PUBLIC ROUTES (no authentication needed)
    // ═══════════════════════════════════════════

    // Auth
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Browse hotels
    Route::get('/hotels', [HotelController::class, 'index']);
    Route::get('/hotels/{hotel:slug}', [HotelController::class, 'show']);

    // Search with availability
    Route::get('/search', [AvailabilityController::class, 'search']);
    Route::get('/hotels/{hotel}/availability', [AvailabilityController::class, 'checkHotel']);

    // Reviews
    Route::get('/hotels/{hotel}/reviews', [ReviewController::class, 'index']);

    // Stripe webhook (no auth - Stripe calls this)
    Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);


    // ═══════════════════════════════════════════
    // PROTECTED ROUTES (authentication required)
    // ═══════════════════════════════════════════

    Route::middleware('auth:sanctum')->group(function () {

        // Auth management
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);

        // ──── Guest: Bookings ────
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

        // ──── Guest: Payments ────
        Route::post('/bookings/{booking}/pay', [PaymentController::class, 'processPayment']);
        Route::get('/bookings/{booking}/payment-status', [PaymentController::class, 'status']);

        // ──── Guest: Reviews ────
        Route::post('/bookings/{booking}/review', [ReviewController::class, 'store']);

        // ═══════════════════════════════════════
        // INVOICE ROUTES (Authenticated)
        // ═══════════════════════════════════════

        // Guest: View & download own invoices
        Route::get('/bookings/{booking}/invoice', [InvoiceController::class, 'view']);
        Route::get('/bookings/{booking}/invoice/download', [InvoiceController::class, 'download']);
        Route::post('/bookings/{booking}/invoice/email', [InvoiceController::class, 'email']);


        // ═══════════════════════════════════════
        // HOTEL OWNER ROUTES
        // ═══════════════════════════════════════

        Route::middleware('role:hotel-owner')->prefix('manage')->group(function () {

            // Hotel CRUD
            Route::apiResource('hotels', AdminHotelController::class);

            // Hotel Owner: Regenerate invoices
            Route::post('/bookings/{booking}/invoice/regenerate', [InvoiceController::class, 'regenerate']);

            // Room Types (nested under hotels)
            Route::apiResource('hotels.room-types', RoomTypeController::class)->shallow();

            // View bookings for my hotels
            Route::get('/hotels/{hotel}/bookings', [AdminBookingController::class, 'index']);

            // Update booking status (check-in, check-out)
            Route::put('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus']);

            // ═══════════════════════════════════════
            // IMAGE UPLOAD ROUTES
            // ═══════════════════════════════════════

            // Hotel images
            Route::post('/hotels/{hotel}/images', [ImageUploadController::class, 'uploadHotelImages']);
            Route::put('/hotels/{hotel}/images', [ImageUploadController::class, 'replaceHotelImages']);
            Route::delete('/hotels/{hotel}/images', [ImageUploadController::class, 'deleteHotelImage']);
            Route::put('/hotels/{hotel}/images/reorder', [ImageUploadController::class, 'reorderHotelImages']);

            // Room type images
            Route::post('/room-types/{roomType}/images', [ImageUploadController::class, 'uploadRoomTypeImages']);
            Route::delete('/room-types/{roomType}/images', [ImageUploadController::class, 'deleteRoomTypeImage']);
        });
    });
});
