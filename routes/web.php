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

// Routes for authentication and registration

Route::middleware(['guest'])->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

});


Route::middleware(['auth', 'signed'])->group(function () {
    Route::get('verification/two-factor', [LoginController::class, 'showTwoFactorForm'])->name('verification.twoFactorForm')->middleware('ensure.not.verified');
    Route::get('verification/notice', [RegisterController::class, 'showVerificationNotice'])->name('verification.notice');
});

// Routes for email verification
Route::post('verification/verify', [RegisterController::class, 'verify'])->name('verification.verify');

// Route for code verification resend
Route::post('verification/resend', [RegisterController::class, 'verificationResend'])->name('verification.resend');
Route::post('verification/resendTwoFactor', [LoginController::class, 'verificationResendTwoFactor'])->name('verification.resendTwoFactor');

// Routes for two-factor authentication
Route::post('verification/two-factor', [LoginController::class, 'verifyTwoFactor'])->name('verification.twoFactor');

// Route for logout
Route::post('logout', [LoginController::class, 'logout'])->name('logout');


// Route for dashboard view
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/home', function () {
        return view('home');
    })->name('home');
});
