<?php

use App\Http\Controllers\Auth\LinkController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', LoginController::class)->middleware('throttle:login');

Route::prefix('auth/link')->group(function () {
    Route::post('/', [LinkController::class, 'request'])->middleware('throttle:auth-link');
    Route::post('/verify', [LinkController::class, 'verify'])->middleware('throttle:auth-link-verify');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
