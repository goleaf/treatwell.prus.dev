<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes - DISABLED
// --------------------------
// All Backpack admin routes have been disabled
// Authentication and admin functionality removed

// All admin routes are commented out to disable admin functionality
/*
Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('country', 'CountryCrudController');
    Route::crud('city', 'CityCrudController');
    Route::crud('venue', 'VenueCrudController');
    Route::crud('location', 'LocationCrudController');
    Route::crud('treatment', 'TreatmentCrudController');
    Route::crud('rating', 'RatingCrudController');
}); // this should be the absolute last line of this file
*/

/**
 * DO NOT ADD ANYTHING HERE.
 */
