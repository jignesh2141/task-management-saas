<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TaskController;
use App\Http\Middleware\InitializeTenancyByRequestDataCustom;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes (no tenant required)
Route::post('/auth/register', [AuthController::class, 'register']);

// Login route - handles tenant_id in request body, so no middleware needed
Route::post('/auth/login', [AuthController::class, 'login']);

// Routes that require tenant identification via middleware
Route::middleware([InitializeTenancyByRequestDataCustom::class])->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Dashboard routes
        Route::get('/dashboard/widgets', [DashboardController::class, 'widgets']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        // Subscription routes
        Route::get('/subscription/current', [SubscriptionController::class, 'current']);
        Route::get('/subscription/plans', [SubscriptionController::class, 'plans']);
        Route::get('/subscription/features', [SubscriptionController::class, 'features']);
        Route::post('/subscription/upgrade', [SubscriptionController::class, 'upgrade']);
        Route::post('/subscription/downgrade', [SubscriptionController::class, 'downgrade']);

        // Task routes
        Route::apiResource('tasks', TaskController::class);
    });
});
