<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();

            // Which hotel does this room type belong to?
            $table->foreignId('hotel_id')
                ->constrained()
                ->onDelete('cascade');

            // Room type details
            $table->string('name');        // "Standard", "Deluxe", "Suite"
            $table->text('description')->nullable();

            // decimal(10,2) = up to 99999999.99
            // Perfect for prices
            $table->decimal('price_per_night', 10, 2);

            // How many guests can stay in this room type?
            $table->unsignedInteger('capacity')->default(2);

            // How many physical rooms of this type exist?
            $table->unsignedInteger('total_rooms')->default(1);

            $table->json('amenities')->nullable();
            $table->json('images')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
