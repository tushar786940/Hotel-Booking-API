<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to
| a specific PHPUnit test case class. By default, that class is
| "PHPUnit\Framework\TestCase". We'll change it to Laravel's
| TestCase so we have access to Laravel's testing helpers.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Refresh Database
|--------------------------------------------------------------------------
|
| RefreshDatabase trait resets the database before EACH test.
| This ensures tests don't interfere with each other.
|
| WHY? If Test A creates a booking and Test B expects 0 bookings,
| Test B would fail without a fresh database.
|
*/

uses(RefreshDatabase::class)->in('Feature');