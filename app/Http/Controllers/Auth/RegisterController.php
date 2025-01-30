<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Mail\VerificationEmail;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }
    
    /**
     * Handle an incoming registration request.
     * 
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\View
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:30|min:3|alpha',
            'last_name' => 'required|string|max:30|min:3|alpha',
            'email' => 'required|string|email|max:60|unique:users',
            'password' => 'required|string|min:8|confirmed|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'g-recaptcha-response' => 'required', //reCAPTCHA validation
        ]);

        if ($validator->fails()) {
            return redirect()->route('register')->withErrors($validator)->withInput();
        }
        
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => '6LeSXsYqAAAAAOtwsniu326XIVfy8wC1gN4cyHr-', 
            'response' => $request->input('g-recaptcha-response'),
        ]);
    
        $responseBody = $response->json();

        Log::info($responseBody);
    
        if (!$responseBody['success']) {
            return back()->withErrors(['captcha' => 'Captcha validation failed.']);
        }
        
        //Generate a verification code
        $verificationCode = Str::random(6); // Code of 6 characters
        $encryptedCode = Crypt::encrypt($verificationCode); // Encrypt the code

        // Create a new user
        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verification_code' => $encryptedCode,
            'verification_code_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send the email with the verification code
        Mail::to($user->email)->send(new VerificationEmail($verificationCode));

        // Redirect to the verification page with the email
        return redirect()->route('verification.notice')->with('status', 'Se ha enviado un código de verificación a tu correo.');

    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->input('email'))->first(); // Get the authenticated user

        $verificationDate = $user->verification_code_expires_at;
        $verificationDate = Carbon::parse($verificationDate);

        // Decrypt the verification code from the database
        $decryptedCode = Crypt::decrypt($user->verification_code);

        $wait = (!$verificationDate || $verificationDate === '0000-00-00 00:00:00' || Carbon::now()->lessThan($verificationDate)) ? true : false;

        // Check if the code is correct and has not expired
        if ($request->input('code') === $decryptedCode) {
            if (!$wait) {
                return back()->withErrors(['verification_code' => 'El código de verificación ha expirado.']);
            }

            // Mark the email as verified
            $user->email_verified_at = now();
            // Clean the verification code
            $user->verification_code = null; 
            // Clean the expiration date
            $user->verification_code_expires_at = null; 
            $user->save();

            return redirect()->route('home')->with('success', 'Correo verificado correctamente.');
        }

        return redirect()->route('verification.notice')->with('error', 'Código inválido o expirado.');
    }

    /**
     * Show the email verification notice.
     * 
     * @return \Illuminate\Http\Response
     */
    
    public function showVerificationNotice()
    {
        return view('auth.verify');
    }


    /**
     * Resend the email verification code.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verificationResend(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        $verificationDate = $user->verification_code_expires_at;
        $verificationDate = Carbon::parse($verificationDate);
    
        $wait = (!$verificationDate || $verificationDate === '0000-00-00 00:00:00' || Carbon::now()->lessThan($verificationDate)) ? true : false;

    
        Log::info('ESPERAS O NO', [
            'wait' => $wait,
            'verification_date' => $verificationDate,
            'current_date' => Carbon::now(),
        ]);

        // Verificar si ya existe un código de verificación en la base de datos y si ha expirado

        if ($user->verification_code && $wait) {
            return redirect()->route('verification.notice')->with('error', 'Debes esperar antes de reenviar el correo.');
        }

        // Generar un nuevo código de verificación
        $verificationCode = Str::random(6);
        $encryptedCode = Crypt::encrypt($verificationCode); 
        $expirationTime = Carbon::now()->addMinutes(10);

        // Guardar el nuevo código y la fecha de expiración en la base de datos
        $user->verification_code = $encryptedCode;
        $user->verification_code_expires_at = $expirationTime;
        $user->save();

        // Enviar el correo con el código de verificación
        Mail::to($user->email)->send(new VerificationEmail($verificationCode));

        session(['email' => $request->email]);

        return redirect()->route('verification.notice')->with('success', 'Correo de verificación reenviado.');
    }

    
}
