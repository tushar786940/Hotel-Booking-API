<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Human-readable booking reference like "BK-A8Kx9mPq"
            // Easier for customers to quote than numeric IDs
            $table->string('booking_reference')->unique();

            // WHO booked?
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            // WHICH room?
            $table->foreignId('room_id')
                  ->constrained()
                  ->onDelete('cascade');

            // WHICH hotel? (denormalized for faster queries)
            // We COULD get hotel through room->roomType->hotel
            // but storing it directly makes queries much faster
            $table->foreignId('hotel_id')
                  ->constrained()
                  ->onDelete('cascade');

            // WHEN?
            $table->date('check_in');
            $table->date('check_out');

            // HOW MANY guests?
            $table->unsignedInteger('guests_count')->default(1);

            // HOW MUCH?
            $table->decimal('total_price', 10, 2);

            // Booking lifecycle status
            $table->enum('status', [
                'pending',      // Just created, awaiting payment
                'confirmed',    // Payment received
                'checked_in',   // Guest has arrived
                'checked_out',  // Guest has left
                'cancelled',    // Booking was cancelled
                'refunded',     // Money returned
            ])->default('pending');

            $table->text('special_requests')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            $table->timestamps();

            // We often search bookings by date range
            $table->index(['check_in', 'check_out']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};