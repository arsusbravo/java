<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\HomeController;
use App\Admin\Admin;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PlaceController;
use App\Http\Controllers\Admin\ResourceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('home.about');

// Destinations
Route::get('/destinations', [DestinationController::class, 'index'])->name('destinations.index');
Route::get('/destinations/{region}', [RegionController::class, 'show'])->name('destinations.region');
Route::get('/destinations/{region}/{slug}', [DestinationController::class, 'show'])->name('destinations.show');

// Articles
Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{id}-{slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/guides', [ArticleController::class, 'guides'])->name('articles.guides');
Route::get('/plan', [ArticleController::class, 'plan'])->name('articles.plan');

Route::get('dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

// Admin Routes
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('/places', [PlaceController::class, 'index'])->name('admin.places.index');
    
    // Region routes
    Route::post('/places/regions', [PlaceController::class, 'storeRegion'])->name('admin.places.regions.store');
    Route::put('/places/regions/order', [PlaceController::class, 'reorderRegions'])->name('admin.places.regions.reorder');
    Route::put('/places/regions/{region}', [PlaceController::class, 'updateRegion'])->name('admin.places.regions.update');
    Route::delete('/places/regions/{region}', [PlaceController::class, 'destroyRegion'])->name('admin.places.regions.destroy');
    
    // Destination routes
    Route::post('/places/destinations', [PlaceController::class, 'storeDestination'])->name('admin.places.destinations.store');
    Route::put('/places/destinations/{destination}', [PlaceController::class, 'updateDestination'])->name('admin.places.destinations.update');
    Route::delete('/places/destinations/{destination}', [PlaceController::class, 'destroyDestination'])->name('admin.places.destinations.destroy');

    Route::post('/editor-images', [ResourceController::class, 'editorImage'])->name('admin.editor-images.store');

    // Generic CRUD for every table registered in App\Admin\Admin
    Route::controller(ResourceController::class)
        ->where(['resource' => implode('|', Admin::keys()), 'id' => '[0-9]+'])
        ->name('admin.resources.')
        ->group(function () {
            Route::get('/{resource}', 'index')->name('index');
            Route::get('/{resource}/create', 'create')->name('create');
            Route::post('/{resource}', 'store')->name('store');
            Route::get('/{resource}/{id}/edit', 'edit')->name('edit');
            Route::put('/{resource}/{id}', 'update')->name('update');
            Route::delete('/{resource}/{id}', 'destroy')->name('destroy');
            Route::post('/{resource}/{id}/actions/{action}', 'action')->name('action');
            Route::get('/{resource}/fields/{field}/options', 'fieldOptions')->name('field-options');
        });
});

require __DIR__.'/settings.php';