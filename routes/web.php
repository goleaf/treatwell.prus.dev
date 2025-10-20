<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Dashboard
Route::get('/', [VenueController::class, 'dashboard'])->name('dashboard');

// Venue routes
Route::get('/venues', [App\Http\Controllers\VenueController::class, 'index'])->name('venues.index');
Route::get('/venues/{slug}', [App\Http\Controllers\VenueController::class, 'showBySlug'])->name('venues.show');
Route::get('/cities/{city}/venues', [VenueController::class, 'byCity'])->name('venues.by-city');

// City routes
Route::get('/cities', [\App\Http\Controllers\CityController::class, 'index'])->name('cities.index');
Route::get('/cities/{city}', [\App\Http\Controllers\CityController::class, 'show'])->name('cities.show');
Route::get('/main-cities', [\App\Http\Controllers\CityController::class, 'mainCities'])->name('cities.main');
Route::get('/subregions', [\App\Http\Controllers\CityController::class, 'subregions'])->name('cities.subregions');

// Admin
Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
Route::post('/admin/scrape', [AdminController::class, 'scrapeCity'])->name('admin.scrape');
Route::post('/admin/scrape-all', [AdminController::class, 'scrapeAllCities'])->name('admin.scrape-all');
