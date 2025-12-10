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

// Additional country routes
Route::get('/countries/active', [\App\Http\Controllers\Api\CountryController::class, 'active']);
Route::get('/countries/with-cities-count', [\App\Http\Controllers\Api\CountryController::class, 'withCitiesCount']);

// Additional service routes
Route::get('/service-categories', [\App\Http\Controllers\Api\ServiceController::class, 'categories']);
Route::get('/services/active', [\App\Http\Controllers\Api\ServiceController::class, 'active']);
Route::get('/services/featured', [\App\Http\Controllers\Api\ServiceController::class, 'featured']);

// Additional procedure routes
Route::get('/procedure-categories', [\App\Http\Controllers\Api\ProcedureController::class, 'categories']);
Route::get('/procedures/active', [\App\Http\Controllers\Api\ProcedureController::class, 'active']);
Route::get('/procedures/with-venues-count', [\App\Http\Controllers\Api\ProcedureController::class, 'withVenuesCount']);
Route::get('/procedures/with-cities-count', [\App\Http\Controllers\Api\ProcedureController::class, 'withCitiesCount']);

// Additional user routes
Route::get('/users/profile', [\App\Http\Controllers\Api\UserController::class, 'profile'])->middleware('auth:sanctum');
Route::get('/users/admins', [\App\Http\Controllers\Api\UserController::class, 'admins']);

// Additional rating routes
Route::get('/ratings/verified', [\App\Http\Controllers\Api\RatingController::class, 'verified']);
Route::get('/ratings/stats', [\App\Http\Controllers\Api\RatingController::class, 'stats']);

// Additional opening hour routes
Route::get('/opening-hours/venue/{venueId}', [\App\Http\Controllers\Api\OpeningHourController::class, 'byVenue']);
Route::get('/opening-hours/today', [\App\Http\Controllers\Api\OpeningHourController::class, 'today']);
Route::get('/opening-hours/currently-open', [\App\Http\Controllers\Api\OpeningHourController::class, 'currentlyOpen']);

// Additional image routes
Route::get('/images/primary', [\App\Http\Controllers\Api\ImageController::class, 'primary']);
Route::get('/images/by-model', [\App\Http\Controllers\Api\ImageController::class, 'byModel']);
Route::patch('/images/{image}/set-primary', [\App\Http\Controllers\Api\ImageController::class, 'setPrimary']);
Route::patch('/images/reorder', [\App\Http\Controllers\Api\ImageController::class, 'reorder']);

// Additional location routes
Route::get('/locations/active', [\App\Http\Controllers\Api\LocationController::class, 'active']);
Route::get('/locations/nearby', [\App\Http\Controllers\Api\LocationController::class, 'nearby']);
Route::get('/locations/city/{cityId}', [\App\Http\Controllers\Api\LocationController::class, 'byCity']);
Route::get('/locations/with-coordinates', [\App\Http\Controllers\Api\LocationController::class, 'withCoordinates']);

// Country resource routes
Route::apiResource('countries', \App\Http\Controllers\Api\CountryController::class);

// Service resource routes
Route::apiResource('services', \App\Http\Controllers\Api\ServiceController::class);

// Procedure resource routes
Route::apiResource('procedures', \App\Http\Controllers\Api\ProcedureController::class);

// User resource routes
Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);

// Rating resource routes
Route::apiResource('ratings', \App\Http\Controllers\Api\RatingController::class);

// Opening hour resource routes
Route::apiResource('opening-hours', \App\Http\Controllers\Api\OpeningHourController::class);

// Image resource routes
Route::apiResource('images', \App\Http\Controllers\Api\ImageController::class);

// Location resource routes
Route::apiResource('locations', \App\Http\Controllers\Api\LocationController::class);
