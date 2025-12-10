<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



// Additional venue routes (must come before resource routes to avoid conflicts)
Route::get('/venue-cities', [\App\Http\Controllers\Api\VenueController::class, 'cities']);
Route::get('/venue-types', [\App\Http\Controllers\Api\VenueController::class, 'types']);
Route::get('/venue-stats', [\App\Http\Controllers\Api\VenueController::class, 'stats']);

// Additional treatment routes (must come before resource routes to avoid conflicts)
Route::get('/treatment-categories', [\App\Http\Controllers\Api\TreatmentController::class, 'categories']);
Route::get('/treatment-venues', [\App\Http\Controllers\Api\TreatmentController::class, 'venuesByTreatment']);
Route::get('/treatment-venues-by-price', [\App\Http\Controllers\Api\TreatmentController::class, 'venuesByPriceRange']);
Route::get('/treatment-price-stats', [\App\Http\Controllers\Api\TreatmentController::class, 'priceStats']);

// City resource routes
Route::apiResource('cities', \App\Http\Controllers\Api\CityController::class);

// Venue resource routes
Route::apiResource('venues', \App\Http\Controllers\Api\VenueController::class);

// Treatment resource routes
Route::apiResource('treatments', \App\Http\Controllers\Api\TreatmentController::class);
