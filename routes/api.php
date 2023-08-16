<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\LegalTextController;
use App\Http\Controllers\Api\PlaceController;
use App\Http\Controllers\Api\PlaceDeletionController;
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

/**
 * To be able to use following routes, it is
 * necessary to provide the Header API_KEY in
 * the request.
 */
Route::prefix('v1')->group(function () {
    Route::get('/legal-text/{type}', [LegalTextController::class, 'getLegalText']);
    Route::get('/faqs', [FaqController::class, 'getFaqs']);

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/login-by-provider', [AuthController::class, 'loginByProvider']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/check-email', [AuthController::class, 'checkEmail']);
        Route::post('/password-recover', [AuthController::class, 'sendResetPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    /**
     * The following routes require the Authorization Header Token,
     * in the "Bearer " format. This token is returned in the
     * response of the endpoints "/auth/login", "/auth/login-by-provider"
     * and "/auth/register".
     */
    Route::middleware(['auth:sanctum', 'email-confirmed'])->group(function () {
        Route::prefix('profile')->group(function () {
            Route::get('/', [AuthController::class, 'getAuthenticated']);
            Route::post('/update', [AuthController::class, 'update']);
        });

        Route::prefix('places')->group(function () {
            Route::get('/', [PlaceController::class, 'listPlaces']);
            Route::get('/radius', [PlaceController::class, 'listPlacesByRadius']);
            Route::post('/', [PlaceController::class, 'createPlace']);
            Route::post('/{placeId}/media', [PlaceController::class, 'attachMediaToPlace']);
            Route::get('/{id}', [PlaceController::class, 'getPlaceById']);
            Route::get('/google/{id}', [PlaceController::class, 'getPlaceByGooglePlaceId']);

            Route::prefix('/delete')->group(function () {
                Route::post('/', [PlaceDeletionController::class, 'handle']);
            });
        });

        Route::prefix('place-evaluations')->group(function () {
            Route::get('/', [PlaceEvaluationController::class, 'listPlaceEvaluations']);
            Route::post('/', [PlaceEvaluationController::class, 'createPlaceEvaluation']);
            Route::delete('/', [PlaceEvaluationController::class, 'deletePlaceEvaluation']);
            Route::post('/{placeEvaluationId}/media', [PlaceEvaluationController::class, 'attachMediaPlaceEvaluationByAuthenticated']);
        });

        Route::prefix('auth')->group(function () {
            Route::get('/place-evaluations', [PlaceEvaluationController::class, 'listPlaceEvaluationsByAppUser']);
        });

        Route::get('/place-rate-settings', [RateSettingsController::class, 'getPlaceRateSettings']);
    });
});
