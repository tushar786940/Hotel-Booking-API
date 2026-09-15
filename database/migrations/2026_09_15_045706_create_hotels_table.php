<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();

            // WHO owns this hotel?
            // foreignId() creates a column that references another table
            // constrained() adds a foreign key constraint
            // onDelete('cascade') = if user is deleted, delete their hotels too
            $table->foreignId('user_id')
                ->constrained()        // References 'id' on 'users' table
                ->onDelete('cascade'); // Delete hotels if owner is deleted

            // Hotel information
            $table->string('name');
            $table->string('slug')->unique(); // URL-friendly name: "grand-palace-hotel"
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('zip_code')->nullable();

            // Location coordinates for map integration
            // decimal(10,8) gives us precise GPS coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Hotel details
            // unsignedTinyInteger = 0-255, perfect for star rating 1-5
            $table->unsignedTinyInteger('star_rating')->default(3);
            $table->time('check_in_time')->default('14:00');
            $table->time('check_out_time')->default('11:00');

            // json() stores arrays/objects as JSON in the database
            // Perfect for lists like images and amenities
            $table->json('images')->nullable();
            $table->json('amenities')->nullable();

            // boolean = true/false. Can deactivate hotels without deleting
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes make searching FASTER
            // We often search by city+country, so we index them
            $table->index(['city', 'country']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
