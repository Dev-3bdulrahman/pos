<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admin/pos')->name('admin.pos.')->group(function () {
    Route::get('/terminals', \Dev3bdulrahman\Pos\Http\Controllers\Web\Admin\Pos\Terminals::class)->name('terminals');
    Route::get('/shifts', \Dev3bdulrahman\Pos\Http\Controllers\Web\Admin\Pos\Shifts::class)->name('shifts');
    Route::get('/register', \Dev3bdulrahman\Pos\Http\Controllers\Web\Admin\Pos\Register::class)->name('register');
});
