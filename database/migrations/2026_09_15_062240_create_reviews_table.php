<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('hotel_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('booking_id')
                ->constrained()
                ->onDelete('cascade');

            // Rating from 1 to 5 stars
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();

            $table->timestamps();

            // One review per booking
            // A user can't review the same booking twice
            $table->unique(['user_id', 'booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
