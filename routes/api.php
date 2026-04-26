<?php

use App\Http\Controllers\Api\V1\PlayerController;
use App\Http\Controllers\Api\V1\TeamController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::middleware(['auth:sanctum'])->get('/user', fn (Request $request) => $request->user()->fresh())->name('user');

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/team', [TeamController::class, 'show'])->name('team.show');
    Route::patch('/team', [TeamController::class, 'update'])->name('team.update');
    Route::get('/players/{player}', [PlayerController::class, 'show'])->name('players.show');
    Route::patch('/players/{player}', [PlayerController::class, 'update'])->name('players.update');
});
