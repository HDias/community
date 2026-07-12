<?php

use App\Http\Controllers\Api\BrasilApiController;
use App\Http\Controllers\Communities\CommunityController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('communities', CommunityController::class)->except(['show', 'create']);
    Route::post('communities/{community}/switch', [CommunityController::class, 'switchCommunity'])
        ->name('communities.switch');

    Route::get('api/brasil/states', [BrasilApiController::class, 'states'])->name('api.brasil.states');
    Route::get('api/brasil/cities/{uf}', [BrasilApiController::class, 'cities'])->name('api.brasil.cities');
});

require __DIR__.'/settings.php';
