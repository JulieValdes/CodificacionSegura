<?php

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
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Show the email verification notice
Route::get('verification/notice', [RegisterController::class, 'showVerificationNotice'])->name('verification.notice');
Route::post('verification/verify', [RegisterController::class, 'verify'])->name('verification.verify');
Route::post('verification/resend', [RegisterController::class, 'verificationResend'])->name('verification.resend');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// Logout route
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Two factor authentication routes
Route::get('verification/two-factor', [LoginController::class, 'showTwoFactorForm'])->name('verification.twoFactorForm');
Route::post('verification/two-factor', [LoginController::class, 'verifyTwoFactor']);

//home route
Route::get('/home', function () {
    return view('home');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

