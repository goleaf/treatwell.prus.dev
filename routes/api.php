<?php

use App\Http\Controllers\Api\TreatmentController;
use App\Http\Controllers\Api\VenueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Venues API routes
Route::get('/venues', [VenueController::class, 'index']);
Route::get('/venues/{venue}', [VenueController::class, 'show']);
Route::get('/cities', [VenueController::class, 'cities']);
Route::get('/cities/{city}/venues', [VenueController::class, 'byCity']);
Route::get('/types', [VenueController::class, 'types']);
Route::get('/stats', [VenueController::class, 'stats']);

// City and subregion API routes
Route::get('/cities-all', [\App\Http\Controllers\Api\CityController::class, 'index']);
Route::get('/main-cities', [\App\Http\Controllers\Api\CityController::class, 'mainCities']);
Route::get('/main-cities/{city}/subregions', [\App\Http\Controllers\Api\CityController::class, 'subregions']);
Route::get('/subregions', [\App\Http\Controllers\Api\CityController::class, 'allSubregions']);
Route::get('/cities-search', [\App\Http\Controllers\Api\CityController::class, 'search']);

// Treatments API routes
Route::get('/treatments/categories', [TreatmentController::class, 'categories']);
Route::get('/treatments/venues', [TreatmentController::class, 'venuesByTreatment']);
Route::get('/treatments/venues/price-range', [TreatmentController::class, 'venuesByPriceRange']);
Route::get('/treatments/price-stats', [TreatmentController::class, 'priceStats']); 