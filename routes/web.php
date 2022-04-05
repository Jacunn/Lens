<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\LoginController;

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

// Google Auth
Route::group(['middleware' => ['guest']], function () {
    Route::get('/login', [LoginController::class, 'redirectToProvider'])->name('login');
    Route::get('/google-login', [LoginController::class, 'handleProviderCallback'])->name('google-login');
});

// Lens Pages
Route::group(['middleware' => ['auth']], function () {
    Route::get('/', function() {
        return redirect('/database-select');
    });
    
    Route::get('/database-select', [Controller::class, 'page_database_select'])->name('database-select');
    Route::get('/query-crafter', [Controller::class, 'page_query_crafter'])->name('query-crafter');
    Route::get('/table-view', [Controller::class, 'page_table_view'])->name('table-view');
    Route::get('/table-data', [Controller::class, 'callback_table_view'])->name('table-data');
    Route::get('/table-csv', [Controller::class, 'callback_table_csv'])->name('table-csv');
});