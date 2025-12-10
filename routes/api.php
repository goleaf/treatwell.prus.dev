<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/venues', [\App\Http\Controllers\Api\VenueController::class, 'index']);
Route::get('/venues/{slug}', [\App\Http\Controllers\Api\VenueController::class, 'show']);
Route::get('/cities', [\App\Http\Controllers\Api\VenueController::class, 'cities']);
Route::get('/types', [\App\Http\Controllers\Api\VenueController::class, 'types']);
Route::get('/stats', [\App\Http\Controllers\Api\VenueController::class, 'stats']);

Route::get('/treatments/categories', [\App\Http\Controllers\Api\TreatmentController::class, 'categories']);
Route::get('/treatments/venues', [\App\Http\Controllers\Api\TreatmentController::class, 'venuesByTreatment']);
Route::get('/treatments/venues/price-range', [\App\Http\Controllers\Api\TreatmentController::class, 'venuesByPriceRange']);
Route::get('/treatments/price-stats', [\App\Http\Controllers\Api\TreatmentController::class, 'priceStats']);
