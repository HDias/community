<?php

use App\Http\Controllers\Api\BrasilApiController;
use App\Http\Controllers\Communities\AdministrationController;
use App\Http\Controllers\Communities\CommunityController;
use App\Http\Controllers\Communities\PositionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('communities', CommunityController::class)->except(['show', 'create']);
    Route::post('communities/{community}/switch', [CommunityController::class, 'switchCommunity'])
        ->name('communities.switch');

    Route::resource('positions', PositionController::class)->except(['create', 'show', 'edit']);
    Route::resource('administrations', AdministrationController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('administrations/{administration}/members', [AdministrationController::class, 'assignMember'])
        ->name('administrations.members.store');
    Route::get('administrations/{administration}/members/search', [AdministrationController::class, 'searchMembers'])
        ->name('administrations.members.search');
    Route::delete('administrations/{administration}/members/{user}', [AdministrationController::class, 'removeMember'])
        ->name('administrations.members.destroy');

    Route::get('api/brasil/states', [BrasilApiController::class, 'states'])->name('api.brasil.states');
    Route::get('api/brasil/cities/{uf}', [BrasilApiController::class, 'cities'])->name('api.brasil.cities');
});

require __DIR__.'/settings.php';
