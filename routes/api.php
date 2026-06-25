<?php

use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/tickets', [TicketController::class, 'index'])  ->middleware(['iae.auth']);
    Route::post('/tickets', [TicketController::class, 'store'])->middleware(['iae.auth']);
    Route::get('/tickets/{id}', [TicketController::class, 'show'])->whereNumber('id')->middleware(['iae.auth']);
    Route::post('/tickets/{id}/payments', [TicketController::class, 'pay'])->whereNumber('id')->middleware(['iae.auth']);
    Route::post('/tickets/{id}/send', [TicketController::class, 'send'])->whereNumber('id')->middleware(['iae.auth']);
});