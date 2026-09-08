<?php

use App\Http\Controllers\AssayController;
use App\Http\Controllers\AssayFieldController;
use App\Http\Controllers\AssayTypeController;
use App\Http\Controllers\ClientCategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CvarController;
use App\Http\Controllers\MatrixController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SampleProcedureController;
use App\Http\Controllers\SampleProcedureFieldController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGroupController;
use App\Models\Assay;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('welcome');

Route::view('/dashboard', 'dashboard')
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'can:clients.view'])->prefix('laboratorium')->group(function () {
    Route::get('/klanten', [ClientController::class, 'index'])->name('clients.index');
    Route::middleware('can:clients.manage')->group(function () {
        Route::get('/klanten/toevoegen', [ClientController::class, 'create'])->name('clients.create');
        Route::post('/klanten', [ClientController::class, 'store'])->name('clients.store');
        Route::get('/klanten/{client}/bewerken', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('/klanten/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('/klanten/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    });
    Route::get('/klantcategorieen', [ClientCategoryController::class, 'index'])->name('client-categories.index');
    Route::middleware('can:clients.manage')->group(function () {
        Route::post('/klantcategorieen', [ClientCategoryController::class, 'store'])->name('client-categories.store');
        Route::delete('/klantcategorieen/{clientCategory}', [ClientCategoryController::class, 'destroy'])->name('client-categories.destroy');
    });
});

Route::middleware(['auth', 'can:viewAny,'.Assay::class])->prefix('beheer')->group(function () {
    Route::view('/', 'beheer.dashboard')->name('beheer.dashboard');
    Route::get('/assays', [AssayController::class, 'index'])->name('assays.index');
    Route::get('/assays/create', [AssayController::class, 'create'])->name('assays.create');
    Route::post('/assays', [AssayController::class, 'store'])->name('assays.store');
    Route::get('/assays/{assay}/edit', [AssayController::class, 'edit'])->name('assays.edit');
    Route::put('/assays/{assay}', [AssayController::class, 'update'])->name('assays.update');
    Route::get('/assay-fields', [AssayFieldController::class, 'index'])->name('assay-fields.index');
    Route::get('/assay-fields/create', [AssayFieldController::class, 'create'])->name('assay-fields.create');
    Route::post('/assay-fields', [AssayFieldController::class, 'store'])->name('assay-fields.store');
    Route::get('/assay-fields/{assayField}/edit', [AssayFieldController::class, 'edit'])->name('assay-fields.edit');
    Route::put('/assay-fields/{assayField}', [AssayFieldController::class, 'update'])->name('assay-fields.update');
    Route::delete('/assay-fields/{assayField}', [AssayFieldController::class, 'destroy'])->name('assay-fields.destroy');
    Route::get('/matrices', [MatrixController::class, 'index'])->name('matrices.index');
    Route::get('/matrices/create', [MatrixController::class, 'create'])->name('matrices.create');
    Route::post('/matrices', [MatrixController::class, 'store'])->name('matrices.store');
    Route::get('/matrices/{matrix}/edit', [MatrixController::class, 'edit'])->name('matrices.edit');
    Route::put('/matrices/{matrix}', [MatrixController::class, 'update'])->name('matrices.update');
    Route::delete('/matrices/{matrix}', [MatrixController::class, 'destroy'])->name('matrices.destroy');
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

    Route::middleware('can:sampling-procedures.manage')->group(function () {
        Route::get('/sample-procedures', [SampleProcedureController::class, 'index'])->name('sample-procedures.index');
        Route::get('/sample-procedures/create', [SampleProcedureController::class, 'create'])->name('sample-procedures.create');
        Route::post('/sample-procedures', [SampleProcedureController::class, 'store'])->name('sample-procedures.store');
        Route::get('/sample-procedures/defaults', [SampleProcedureController::class, 'defaults'])->name('sample-procedures.defaults');
        Route::put('/sample-procedures/defaults', [SampleProcedureController::class, 'updateDefaults'])->name('sample-procedures.defaults.update');
        Route::get('/sample-procedures/{sampleProcedure}/edit', [SampleProcedureController::class, 'edit'])->name('sample-procedures.edit');
        Route::put('/sample-procedures/{sampleProcedure}', [SampleProcedureController::class, 'update'])->name('sample-procedures.update');
        Route::delete('/sample-procedures/{sampleProcedure}', [SampleProcedureController::class, 'destroy'])->name('sample-procedures.destroy');
    });

    Route::middleware('can:sampling-procedure-fields.manage')->group(function () {
        Route::get('/sample-procedure-fields', [SampleProcedureFieldController::class, 'index'])->name('sample-procedure-fields.index');
        Route::get('/sample-procedure-fields/create', [SampleProcedureFieldController::class, 'create'])->name('sample-procedure-fields.create');
        Route::post('/sample-procedure-fields', [SampleProcedureFieldController::class, 'store'])->name('sample-procedure-fields.store');
        Route::get('/sample-procedure-fields/{sampleProcedureField}/edit', [SampleProcedureFieldController::class, 'edit'])->name('sample-procedure-fields.edit');
        Route::put('/sample-procedure-fields/{sampleProcedureField}', [SampleProcedureFieldController::class, 'update'])->name('sample-procedure-fields.update');
        Route::delete('/sample-procedure-fields/{sampleProcedureField}', [SampleProcedureFieldController::class, 'destroy'])->name('sample-procedure-fields.destroy');
    });

    Route::middleware('can:users.manage')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });

    Route::middleware('can:groups.manage')->group(function () {
        Route::get('/user-groups', [UserGroupController::class, 'index'])->name('user-groups.index');
        Route::get('/user-groups/create', [UserGroupController::class, 'create'])->name('user-groups.create');
        Route::post('/user-groups', [UserGroupController::class, 'store'])->name('user-groups.store');
        Route::get('/user-groups/{userGroup}/edit', [UserGroupController::class, 'edit'])->name('user-groups.edit');
        Route::put('/user-groups/{userGroup}', [UserGroupController::class, 'update'])->name('user-groups.update');
        Route::delete('/user-groups/{userGroup}', [UserGroupController::class, 'destroy'])->name('user-groups.destroy');
    });

    Route::middleware('can:settings.advanced')->group(function () {
        Route::get('/cvars', [CvarController::class, 'index'])->name('cvars.index');
        Route::put('/cvars/{cvar}', [CvarController::class, 'update'])->name('cvars.update');
    });
});

Route::view('/debug/mockup', 'layouts.mockup')->name('debug.mockup');
Route::view('/debug/mockup-modern', 'layouts.mockup-modern')->name('debug.mockup-modern');
