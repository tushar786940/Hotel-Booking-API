<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_type_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('room_number'); // "D001", "S101"
            $table->unsignedTinyInteger('floor')->default(1);

            // enum() restricts values to a specific list
            // The room can ONLY be one of these three statuses
            $table->enum('status', [
                'available',    // Ready for guests
                'occupied',     // Currently has guests
                'maintenance',  // Being cleaned/repaired
            ])->default('available');

            $table->boolean('is_available')->default(true);

            $table->timestamps();

            // A room number must be unique WITHIN its room type
            // Same hotel can't have two rooms with the same number
            $table->unique(['room_type_id', 'room_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
