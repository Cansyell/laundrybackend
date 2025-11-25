<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AddOnController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('categories', CategoryController::class);

    Route::apiResource('services', ServiceController::class);

    Route::apiResource('customers', CustomerController::class);
    
    Route::apiResource('transactions', TransactionController::class);

    Route::get('/add-ons', [AddOnController::class, 'index']);
    Route::post('/add-ons', [AddOnController::class, 'store']);
    Route::get('/add-ons/{id}', [AddOnController::class, 'show']);
    Route::put('/add-ons/{id}', [AddOnController::class, 'update']);
    Route::delete('/add-ons/{id}', [AddOnController::class, 'destroy']);
    
    // Additional endpoint for statistics
    Route::get('/add-ons-statistics', [AddOnController::class, 'statistics']);

    Route::apiResource('expense-categories', ExpenseCategoryController::class);

    // Expense Routes
    Route::apiResource('expenses', ExpenseController::class);

    // Additional Expense Routes
    Route::prefix('expenses')->group(function () {
        Route::get('summary', [ExpenseController::class, 'summary']);
        Route::get('by-month', [ExpenseController::class, 'byMonth']);
    });
    
});