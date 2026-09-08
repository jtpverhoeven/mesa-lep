<?php

use App\Http\Controllers\AssayController;
use App\Http\Controllers\AssayTypeController;
use App\Http\Controllers\MediaController;
use App\Models\Assay;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('welcome');

Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'can:viewAny,'.Assay::class])->prefix('beheer')->group(function () {
    Route::redirect('/', '/beheer/assays');
    Route::get('/assays', [AssayController::class, 'index'])->name('assays.index');
    Route::get('/assays/create', [AssayController::class, 'create'])->name('assays.create');
    Route::post('/assays', [AssayController::class, 'store'])->name('assays.store');
    Route::get('/assays/{assay}/edit', [AssayController::class, 'edit'])->name('assays.edit');
    Route::put('/assays/{assay}', [AssayController::class, 'update'])->name('assays.update');
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::get('/media/create', [MediaController::class, 'create'])->name('media.create');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::get('/media/{media}/edit', [MediaController::class, 'edit'])->name('media.edit');
    Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get('/assay-types', [AssayTypeController::class, 'index'])->name('assay-types.index');
    Route::get('/assay-types/create', [AssayTypeController::class, 'create'])->name('assay-types.create');
    Route::post('/assay-types', [AssayTypeController::class, 'store'])->name('assay-types.store');
    Route::get('/assay-types/{assayType}/edit', [AssayTypeController::class, 'edit'])->name('assay-types.edit');
    Route::put('/assay-types/{assayType}', [AssayTypeController::class, 'update'])->name('assay-types.update');
    Route::post('/assay-types/{assayType}/fields', [AssayTypeController::class, 'storeField'])->name('assay-type-fields.store');
    Route::get('/assay-types/{assayType}/fields/{field}/edit', [AssayTypeController::class, 'editField'])->name('assay-type-fields.edit');
    Route::put('/assay-types/{assayType}/fields/{field}', [AssayTypeController::class, 'updateField'])->name('assay-type-fields.update');
    Route::delete('/assay-types/{assayType}/fields/{field}', [AssayTypeController::class, 'destroyField'])->name('assay-type-fields.destroy');
});

Route::view('/debug/mockup', 'layouts.mockup')->name('debug.mockup');
Route::view('/debug/mockup-modern', 'layouts.mockup-modern')->name('debug.mockup-modern');
