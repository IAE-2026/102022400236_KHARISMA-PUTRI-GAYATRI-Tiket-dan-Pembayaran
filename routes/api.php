<?php

use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['sso.auth'])->group(function () {
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{id}', [TicketController::class, 'show'])->whereNumber('id');
    Route::post('/tickets/{id}/payments', [TicketController::class, 'pay'])->whereNumber('id');
    Route::post('/tickets/{id}/send', [TicketController::class, 'send'])->whereNumber('id');
});
