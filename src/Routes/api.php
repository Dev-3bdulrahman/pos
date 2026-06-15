<?php

use Illuminate\Support\Facades\Route;
use Dev3bdulrahman\Pos\Http\Controllers\Api\PosApiController;

Route::prefix('api/v1/pos')->middleware(['auth:sanctum', 'throttle:60,1', 'api.tenant'])->group(function () {
    // POS Sales
    Route::get('sales', [PosApiController::class, 'index'])->name('api.v1.pos.sales.index');
    Route::post('sales', [PosApiController::class, 'store'])->name('api.v1.pos.sales.store');
    Route::get('sales/{posSale}', [PosApiController::class, 'show'])->name('api.v1.pos.sales.show');
});
