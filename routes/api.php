<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\LegalTextController;
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

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/login-by-provider', [AuthController::class, 'loginByProvider']);
    Route::post('/auth/password-recover', [AuthController::class, 'passwordRecover']);

    Route::middleware('auth:sanctum')->get('/auth/profile', [AuthController::class, 'getAuthenticated']);

    Route::get('/legal-text/{type}', [LegalTextController::class, 'getLegalText']);
    Route::get('/faqs', [FaqController::class, 'getFaqs']);
});
