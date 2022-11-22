<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\LegalTextController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\PlaceEvaluationController;
use App\Http\Controllers\Api\RateSettingsController;
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

// required ---> API_KEY in Header, security validation
Route::prefix('v1')->group(function () {
    Route::post('/auth/check-email', [AuthController::class, 'checkEmail']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/login-by-provider', [AuthController::class, 'loginByProvider']);
    Route::post('/auth/password-recover', [AuthController::class, 'passwordRecover']);

    // required ---> Authorization Token Header in format (Bearer ) security validation, token return in login,loginByProvider,register function
    Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
        // APP_USER Authenticated, use token to get user DATA

        Route::get('/profile', [AuthController::class, 'getAuthenticated']);
        Route::post('/profile/update', [AuthController::class, 'update']);

        Route::get('/place-evaluations', [PlaceEvaluationController::class, 'listPlaceEvaluationsByAppUser']);
        Route::post('/place-evaluations', [PlaceEvaluationController::class, 'createPlaceEvaluation']);

        Route::post('/place-evaluations/{placeEvaluationId}/media', [PlaceEvaluationController::class, 'attachMediaPlaceEvaluationByAuthenticated']);

        Route::get('/place-rate-settings', [RateSettingsController::class, 'getPlaceRateSettings']);
    });

    Route::get('/places', [PlaceController::class, 'listPlaces']);
    Route::get('/place-evaluations', [PlaceEvaluationController::class, 'listPlaceEvaluations']);

    Route::get('/legal-text/{type}', [LegalTextController::class, 'getLegalText']);
    Route::get('/faqs', [FaqController::class, 'getFaqs']);
});
