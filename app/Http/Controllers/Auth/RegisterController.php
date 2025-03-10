<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Mail\VerificationEmail;
use Carbon\Carbon;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     * 
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }
    
    /**
     * Handle an incoming registration request.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:30|min:3|regex:/^[\pL\s]+$/u',
            'last_name' => 'required|string|max:30|min:3|regex:/^[\pL\s]+$/u',
            'email' => 'required|string|email|max:60|unique:users',
            'password' => 'required|string|min:8|confirmed|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'g-recaptcha-response' => 'required', //reCAPTCHA validation
        ], [
            'name.required' => 'El nombre es requerido.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder los 30 caracteres.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.regex' => 'El nombre solo debe contener letras.',

            'last_name.required' => 'El apellido es requerido.',
            'last_name.string' => 'El apellido debe ser una cadena de texto.',
            'last_name.max' => 'El apellido no debe exceder los 30 caracteres.',
            'last_name.min' => 'El apellido debe tener al menos 3 caracteres.',
            'last_name.regex' => 'El apellido solo debe contener letras.',

            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo válida.',
            'email.max' => 'El correo electrónico no debe exceder los 60 caracteres.',
            'email.unique' => 'El correo electrónico ya está en uso.',

            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.regex' => 'La contraseña debe contener al menos una letra mayúscula, una letra minúscula, un número y un carácter especial.',

            'g-recaptcha-response.required' => 'Por favor, verifica que no eres un robot.',
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
        
        // Generate a verification code
        $verificationCode = Str::random(6); // Code of 6 characters
        $encryptedCode = Crypt::encrypt($verificationCode); // Encrypt the code
        $verificationCodeExpiresAt = Carbon::now()->addMinutes(10);

        // Create a new user
        $user = User::create([
            'name' => $request->name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verification_code' => $encryptedCode,
            'verification_code_expires_at' => $verificationCodeExpiresAt,
        ]);

        session(['email' => $user->email]);

        // Send the email with the verification code
        try {
            Mail::to($user->email)->send(new VerificationEmail($verificationCode));
        } catch (\Exception $e) {
            Log::error('Error al enviar el correo de verificación: ' . $e->getMessage());
            return redirect()->route('register')->with('error', 'Error al enviar el correo de verificación. Por favor, intenta nuevamente.');
        }

        // Redirect to the verification page with the email
        return redirect()->signedRoute('verification.notice', ['email' => $user->email])->with('status', 'Se ha enviado un código de verificación a tu correo.');
    }

    /**
     * Verify the email with the verification code.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
            'g-recaptcha-response' => 'required', //reCAPTCHA validation
        ], [
            'code.required' => 'El código de verificación es requerido.',
            'code.string' => 'El código de verificación debe ser una cadena de texto.',
            'code.size' => 'El código de verificación debe tener 6 caracteres.',

            'g-recaptcha-response.required' => 'Por favor, verifica que no eres un robot.',
        ]);

        if ($validator->fails()) {
            return redirect()->signedRoute('verification.notice', ['email' => $request->email])->withErrors($validator);
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => '6LeSXsYqAAAAAOtwsniu326XIVfy8wC1gN4cyHr-', 
                'response' => $request->input('g-recaptcha-response'),
            ]);
        
            $responseBody = $response->json();

            Log::info($responseBody);
        
            if (!$responseBody['success']) {
                return back()->withErrors(['captcha' => 'Captcha validation failed.']);
            }
        } catch (\Exception $e) {
            Log::error('Error al validar el reCAPTCHA: ' . $e->getMessage());
            return back()->withErrors(['captcha' => 'Error al validar el reCAPTCHA. Por favor, intenta nuevamente.']);
        }

        Log::info(['Request' => $request->all()]);

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return redirect()->route('register')->with('error', 'El usuario no existe.');
        }

        // Decrypt the verification code from the database
        $decryptedCode = Crypt::decrypt($user->verification_code);
        
        // Check if the code is correct and has not expired
        if ($request->input('code') === $decryptedCode) {
            if (!$this->verifyCode($user)) {
                return redirect()->route('verification.notice')->with('error', 'El código de verificación ha expirado.');
            }

            // Mark the email as verified
            $user->email_verified_at = now();
            $user->verification_code = null;
            $user->verification_code_expires_at = null;
            $user->save();

            return redirect()->route('home')->with('success', 'Correo verificado correctamente.');
        }
        return redirect()->signedRoute('verification.notice')->with('error', 'Código inválido o expirado.');
    }

    /**
     * Show the email verification notice.
     * 
     * @return \Illuminate\View\View
     */
    public function showVerificationNotice()
    {
        return view('auth.verify');
    }

    /**
     * Resend the email verification code.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verificationResend(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return redirect()->route('register')->with('error', 'El usuario no existe.');
        }

        // Verify if the user must wait before requesting a new code
        if ($this->verifyCode($user)) {
            return redirect()->signedRoute('verification.notice')->with('error', 'Debes esperar antes de reenviar el correo.');
        }

        // Generate a new verification code and expiration date
        $verificationCode = Str::random(6);
        $encryptedCode = Crypt::encrypt($verificationCode);
        $expirationTime = Carbon::now()->addMinutes(10);

        // Save the new verification code and expiration date in the user's record
        $user->verification_code = $encryptedCode;
        $user->verification_code_expires_at = $expirationTime;
        $user->save();

        // Send the email with the new verification code
        try {
            Mail::to($user->email)->send(new VerificationEmail($verificationCode));
        } catch (\Exception $e) {
            Log::error('Error al enviar el correo de verificación: ' . $e->getMessage());
            return redirect()->route('register')->with('error', 'Error al enviar el correo de verificación. Por favor, intenta nuevamente.');
        }

        return redirect()->signedRoute('verification.notice')->with('success', 'Correo de verificación reenviado.');
    }

    /**
     * Verify if the user must wait before requesting a new code.
     * 
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function verifyCode(User $user)
    {
        if (!$user->verification_code) {
            return false;
        }

        // Get the expiration date of the verification code
        $verificationDate = $user->verification_code_expires_at;
        $verificationDate = Carbon::parse($verificationDate);

        // Determine if the user must wait (true if the code is still valid, false if it has expired)
        $wait = ($verificationDate && Carbon::now()->lessThan($verificationDate));

        Log::info('ESPERAS O NO', [
            'wait' => $wait,
            'verification_date' => $verificationDate,
            'current_date' => Carbon::now(),
        ]);

        return $wait;
    }
}