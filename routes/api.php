<?php

use App\Http\Controllers\Api\AuthController;

Route::post('/login', [AuthController::class, 'login']);
