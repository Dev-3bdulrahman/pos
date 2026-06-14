<?php

use Illuminate\Support\Facades\Route;
use Dev3bdulrahman\Pos\Http\Controllers\Api\PosApiController;

Route::middleware(['api'])->prefix('api/v1/pos')->name('api.v1.pos.')->group(function () {
    Route::get('/terminals', [PosApiController::class, 'getTerminals'])->name('terminals');
    Route::post('/sales', [PosApiController::class, 'storeSale'])->name('sales.store');
});
