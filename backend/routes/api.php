<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\TripController;
use Illuminate\Support\Facades\Route;

Route::get('/trips', [TripController::class, 'index']);
Route::get('/trips/{trip}', [TripController::class, 'show'])->whereNumber('trip');
Route::get('/trips/{trip}/available-seats', [TripController::class, 'availableSeats'])->whereNumber('trip');
Route::post('/bookings', [BookingController::class, 'store']);
