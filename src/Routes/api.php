<?php

use Illuminate\Support\Facades\Route;
use Dev3bdulrahman\Pos\Http\Controllers\Api\PosApiController;

Route::prefix('api/v1/pos')->middleware(['auth:sanctum', 'throttle:60,1', 'api.tenant'])->group(function () {
    // POS Sales
    Route::get('sales', [PosApiController::class, 'index'])->middleware('can:pos.sales.view')->name('api.v1.pos.sales.index');
    Route::post('sales', [PosApiController::class, 'store'])->middleware('can:pos.sales.create')->name('api.v1.pos.sales.store');
    Route::get('sales/{posSale}', [PosApiController::class, 'show'])->middleware('can:pos.sales.view')->name('api.v1.pos.sales.show');

    // Sessions
    Route::get('sessions', [PosApiController::class, 'sessionsIndex'])->middleware('can:pos.sessions.view')->name('api.v1.pos.sessions.index');
    Route::post('sessions/open', [PosApiController::class, 'openSession'])->middleware('can:pos.sessions.create')->name('api.v1.pos.sessions.open');
    Route::post('sessions/{posSession}/close', [PosApiController::class, 'closeSession'])->middleware('can:pos.sessions.create')->name('api.v1.pos.sessions.close');

    // Shifts
    Route::get('shifts', [PosApiController::class, 'shiftsIndex'])->middleware('can:pos.sessions.view')->name('api.v1.pos.shifts.index');

    // Terminals
    Route::get('terminals', [PosApiController::class, 'terminalsIndex'])->middleware('can:pos.sessions.view')->name('api.v1.pos.terminals.index');

    // Cash Movements
    Route::get('cash-movements', [PosApiController::class, 'cashMovementsIndex'])->middleware('can:pos.sales.view')->name('api.v1.pos.cash-movements.index');
    Route::post('cash-movements', [PosApiController::class, 'storeCashMovement'])->middleware('can:pos.sales.create')->name('api.v1.pos.cash-movements.store');
});
