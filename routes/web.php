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

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::middleware(['guest'])->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Verificacion Routes
Route::middleware(['signed', 'guest'])->group(function () {
    Route::get('verification/two-factor', [LoginController::class, 'showTwoFactorForm'])->name('verification.twoFactorForm');
    Route::get('verification/notice', [RegisterController::class, 'showVerificationNotice'])->name('verification.notice');
});

// Email verification
Route::post('verification/verify', [RegisterController::class, 'verify'])->name('verification.verify');
Route::post('verification/resend', [RegisterController::class, 'verificationResend'])->name('verification.resend');

// Two-factor authentication
Route::post('verification/resendTwoFactor', [LoginController::class, 'verificationResendTwoFactor'])->name('verification.resendTwoFactor');
Route::post('verification/two-factor', [LoginController::class, 'verifyTwoFactor'])->name('verification.twoFactor');

Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', function () {
        return view('home');
    })->name('home');
});
