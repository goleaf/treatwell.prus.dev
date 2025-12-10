<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Public venue and treatment routes
Route::get('/venues', [App\Http\Controllers\VenueController::class, 'index'])->name('web.venues.index');
Route::get('/treatments', [App\Http\Controllers\TreatmentController::class, 'index'])->name('web.treatments.index');

// Public CRUD routes (no authentication required)
// Venue CRUD routes
Route::get('/venues/create', [App\Http\Controllers\VenueController::class, 'create'])->name('web.venues.create');
Route::post('/venues', [App\Http\Controllers\VenueController::class, 'store'])->name('web.venues.store');
Route::get('/venues/{venue}/edit', [App\Http\Controllers\VenueController::class, 'edit'])->name('web.venues.edit');
Route::put('/venues/{venue}', [App\Http\Controllers\VenueController::class, 'update'])->name('web.venues.update');
Route::delete('/venues/{venue}', [App\Http\Controllers\VenueController::class, 'destroy'])->name('web.venues.destroy');

// Treatment CRUD routes
Route::get('/treatments/create', [App\Http\Controllers\TreatmentController::class, 'create'])->name('web.treatments.create');
Route::post('/treatments', [App\Http\Controllers\TreatmentController::class, 'store'])->name('web.treatments.store');
Route::get('/treatments/{treatment}/edit', [App\Http\Controllers\TreatmentController::class, 'edit'])->name('web.treatments.edit');
Route::put('/treatments/{treatment}', [App\Http\Controllers\TreatmentController::class, 'update'])->name('web.treatments.update');
Route::delete('/treatments/{treatment}', [App\Http\Controllers\TreatmentController::class, 'destroy'])->name('web.treatments.destroy');

// Public show routes (must come after create/edit routes to avoid conflicts)
Route::get('/venues/{venue}', [App\Http\Controllers\VenueController::class, 'show'])->name('web.venues.show');
Route::get('/treatments/{treatment}', [App\Http\Controllers\TreatmentController::class, 'show'])->name('web.treatments.show');
