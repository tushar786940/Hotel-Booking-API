<?php

use App\Models\RoomType;
use App\Services\PricingService;

/*
|--------------------------------------------------------------------------
| WHY UNIT TESTS?
|--------------------------------------------------------------------------
| Feature tests test the full HTTP flow (request → response).
| Unit tests test individual classes in isolation.
|
| Unit tests are FASTER and more FOCUSED.
| If pricing math is wrong, a unit test tells you exactly where.
|--------------------------------------------------------------------------
*/

test('pricing calculates base price correctly', function () {
    $service  = new PricingService();
    $roomType = RoomType::factory()->make(['price_per_night' => 100]);

    $breakdown = $service->getBreakdown($roomType, 3, 2);

    expect($breakdown['base_price'])->toBe(300.00);
    expect($breakdown['nights'])->toBe(3);
});

test('pricing adds extra guest charge', function () {
    $service  = new PricingService();
    $roomType = RoomType::factory()->make(['price_per_night' => 100]);

    // 4 guests = 2 extra guests (base is 2)
    $breakdown = $service->getBreakdown($roomType, 3, 4);

    // Extra: 2 guests × ($100 × 0.20) × 3 nights = $120
    expect($breakdown['extra_guest_charge'])->toBe(120.00);
    expect($breakdown['extra_guests'])->toBe(2);
});

test('pricing includes tax', function () {
    $service  = new PricingService();
    $roomType = RoomType::factory()->make(['price_per_night' => 100]);

    $breakdown = $service->getBreakdown($roomType, 1, 2);

    // $100 × 12% = $12
    expect($breakdown['tax'])->toBe(12.00);
    expect($breakdown['total'])->toBe(112.00);
});

test('no extra charge for 2 or fewer guests', function () {
    $service  = new PricingService();
    $roomType = RoomType::factory()->make(['price_per_night' => 200]);

    $breakdown = $service->getBreakdown($roomType, 2, 1);

    expect($breakdown['extra_guest_charge'])->toBe(0.00);
    expect($breakdown['extra_guests'])->toBe(0);
});

test('calculateTotal returns correct final amount', function () {
    $service  = new PricingService();
    $roomType = RoomType::factory()->make(['price_per_night' => 150]);

    $total = $service->calculateTotal($roomType, 5, 2);

    // $150 × 5 = $750 + 12% tax = $840
    expect($total)->toBe(840.00);
});