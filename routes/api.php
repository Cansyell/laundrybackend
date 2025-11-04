<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoryController;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);
});