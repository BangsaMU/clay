<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

if (config('adminlte.DASHBOARD_ONLY')) {
    Route::get('/', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm']);
} else {
    Route::get('/', function () {
        return redirect('/login');
    });
}

/*register sesuai dengan fungsi SSO*/
Auth::routes(['register' => !config('SsoConfig.main.ACTIVE')]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
