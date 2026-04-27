<?php

use App\Http\Controllers\Api\V1\PlayerController;
use App\Http\Controllers\Api\V1\TeamController;
use App\Http\Controllers\Api\V1\TransferListingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware(['auth:sanctum'])->get('/user', fn (Request $request) => $request->user()->fresh())->name('user');

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('team')->group(function () {
        Route::get('/', [TeamController::class, 'show'])->name('team.show');
        Route::patch('/', [TeamController::class, 'update'])->name('team.update');
    });

    Route::prefix('players')->group(function () {
        Route::get('/{player}', [PlayerController::class, 'show'])->name('players.show');
        Route::patch('/{player}', [PlayerController::class, 'update'])->name('players.update');
        Route::get('/{player}/transfers', [PlayerController::class, 'transfers'])->name('players.transfers');
    });

    Route::prefix('transfer-listings')->group(function () {
        Route::get('/', [TransferListingController::class, 'index'])->name('transfer-listings.index');
        Route::post('/', [TransferListingController::class, 'store'])->name('transfer-listings.store');
        Route::delete('/{listing}', [TransferListingController::class, 'destroy'])->name('transfer-listings.destroy');
        Route::post('/{listing}/purchase', [TransferListingController::class, 'purchase'])->name('transfer-listings.purchase');
    });
});
