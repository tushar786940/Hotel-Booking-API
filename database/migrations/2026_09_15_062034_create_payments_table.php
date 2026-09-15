<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained()
                ->onDelete('cascade');

            $table->decimal('amount', 10, 2);
            // ISO currency code: USD, EUR, GBP
            $table->string('currency', 3)->default('USD');

            $table->enum('method', ['stripe', 'paypal', 'cash'])
                ->default('stripe');

            // Stripe's payment ID - links our record to Stripe's record
            $table->string('transaction_id')->nullable();

            $table->enum('status', [
                'pending',    // Waiting for payment
                'completed',  // Money received
                'failed',     // Payment failed
                'refunded',   // Money returned
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();

            // Store Stripe's full response for debugging
            $table->json('gateway_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
