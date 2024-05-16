<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\LegalTextController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/terms-conditions', [LegalTextController::class, 'terms']);
Route::get('/privacy-policy', [LegalTextController::class, 'privacy']);
Route::get('/faqs', [FaqController::class, 'faqs']);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('route:cache');
    Artisan::call('config:cache');
    Artisan::call('view:clear');

    return 'Cache has been cleared';
});

require __DIR__.'/auth.php';
