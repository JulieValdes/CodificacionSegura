<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Show the login form
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming login request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:60',
            'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'g-recaptcha-response' => 'required', //reCAPTCHA validation
        ],
        [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico debe ser una dirección de correo válida.',
            'email.max' => 'El correo electrónico no debe exceder los 60 caracteres.',

            'password.required' => 'La contraseña es requerida.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex' => 'La contraseña debe contener al menos una letra mayúscula, una letra minúscula, un número y un carácter especial.',

            'g-recaptcha-response.required' => 'Por favor, verifica que no eres un robot.',

        ]);
        
        if ($validator->fails()) {
            return redirect()->route('login')->withErrors($validator)->withInput();
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
            // Manejar errores de la solicitud HTTP
            Log::error('Error al validar el reCAPTCHA: ' . $e->getMessage());
            return back()->withErrors(['captcha' => 'Error al validar el reCAPTCHA. Por favor, intenta nuevamente.']);
        }

        // Attempt to log in the user
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return redirect()->route('login')->with('error', 'El usuario no existe.');
        }

        if ($user && Hash::check($request->password, $user->password)) {
            
            session(['email' => $user->email]);

            // Verify if the user has verified their email
            if (!$user->email_verified_at) {
                return redirect()->signedRoute('verification.notice')->with('error', 'Por favor verifica tu correo antes de iniciar sesión.');
            }
            
            if ($this->verifyCode($user)) {
                return redirect()->signedRoute('verification.twoFactorForm', ['email' => $user->email])->with('error', 'Debes esperar antes de reenviar el correo.');
            }

            $twoFactorCode = Str::random(6); // Generate a 2FA code
            $encryptedCode = Crypt::encrypt($twoFactorCode); // Encrypt the code

            // Save the 2FA code and expiration time in the user's record
            $user->verification_code = $encryptedCode;
            $user->verification_code_expires_at = Carbon::now()->addMinutes(10); // 10 minutes to verify
            Log::info('Verification code expires at:', ['expires_at' => $user->verification_code_expires_at]);
            $user->save();

            // Enviar código al correo del usuario
            try {
                Mail::to($user->email)->send(new TwoFactorCodeMail($twoFactorCode));
            } catch (\Exception $e) {
                Log::error('Error al enviar el correo de verificación: ' . $e->getMessage());
                return redirect()->route('login')->with('error', 'Error al enviar el correo de verificación. Por favor, intenta nuevamente.');
            }

            // Redirigir al formulario de verificación
            return redirect()->signedRoute('verification.twoFactorForm', ['email' => $user->email])->with('status', 'Se ha enviado un código de verificación a tu correo.');
        }

        // Si las credenciales son incorrectas
        return back()->withErrors(['email' => 'Estas credenciales no coinciden con nuestros registros.']);
    }

    /**
     * Show the two factor authentication form
     *
     * @return \Illuminate\View\View
     */

    public function showTwoFactorForm(Request $request)
    {
        if ($request->session()->get('2fa_verified')) {
            return redirect()->route('home')->with('error', 'Ya has completado la autenticación de dos factores.');
        }

        return view('auth.two-factor'); // Vista para ingresar el código 2FA
    }

    /**
     * Resend the two factor authentication code
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function verificationResendTwoFactor(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($this->verifyCode($user)) {
            return redirect()->signedRoute('verification.twoFactorForm', ['email' => $user->email])->with('error', 'Debes esperar antes de reenviar el correo.');
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
            Mail::to($user->email)->send(new TwoFactorCodeMail($verificationCode));
        } catch (\Exception $e) {
            Log::error('Error al enviar el correo de verificación: ' . $e->getMessage());
            return redirect()->route('verification.twoFactorForm', ['email' => $user->email])->with('error', 'Error al enviar el correo de verificación. Por favor, intenta nuevamente.');
        }	

        session(['email' => $request->email]);;

        return redirect()->signedRoute('verification.twoFactorForm', ['email' => $user->email])->with('status', 'Se ha reenviado un código de verificación a tu correo.');
    }

    /**
     * Verify the two factor authentication code
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */

     public function verifyTwoFactor(Request $request)
     {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
            
            'g-recaptcha-response' => 'required', //reCAPTCHA validation
        ], [
            'code.required' => 'El código de verificación es requerido.',
            'code.size' => 'El código de verificación debe tener 6 caracteres.',
            
            'g-recaptcha-response.required' => 'Por favor, verifica que no eres un robot.',
        ]);
    
        if ($validator->fails()) {
            return redirect()->signedRoute('verification.twoFactorForm', ['email' => $request->email])->withErrors($validator);
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
            return redirect()->route('login')->with('error', 'El usuario no existe.');
        }
    
        // if the user doesn't have a verification code generate one
        if (!$user->verification_code) {
            // Verify if the user must wait before requesting a new code
            if ($this->verifyCode($user)) {
                return redirect()->route('verification.twoFactorForm', ['email' => $user->email])->with('error', 'Debes esperar antes de solicitar un nuevo código.');
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
                Mail::to($user->email)->send(new TwoFactorCodeMail($verificationCode));
            } catch (\Exception $e) {
                Log::error('Error al enviar el correo de verificación: ' . $e->getMessage());
                return redirect()->route('login')->with('error', 'Error al enviar el correo de verificación. Por favor, intenta nuevamente.');
            }
    
            return redirect()->route('verification.twoFactorForm', ['email' => $user->email])->with('status', 'Se ha enviado un código de verificación a tu correo.');
        }
     
        $decryptedCode = Crypt::decrypt($user->verification_code);
     
        if ($request->input('code') === $decryptedCode) {
            // Verify if the user must wait before verifying the code
            if ($this->verifyCode($user)) {

                $user->email_verified_at = now();
                $user->verification_code = null;
                $user->verification_code_expires_at = null;
                $user->save();
    
                Auth::login($user);
    
                Log::info('El usuario ha sido autenticado ' . Auth::check());
    
                return redirect()->route('home')->with('success', 'Autenticación de dos factores completada.');
            } else {
                return redirect()->signedRoute('verification.twoFactorForm', ['email' => $user->email])->with('error', 'El código de verificación ha expirado.');
            }
        }
    
        return redirect()->signedRoute('verification.twoFactorForm', ['email' => $user->email])->with('error', 'Código inválido o ha expirado.');
    }

    /**
     * Logout the user
     *  
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function logout(Request $request)
    {
        $request->session()->forget('2fa_verified');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Usuario ha sido deslogueado');
        
        return redirect('/');
    }

    /**
     * Verify the two factor authentication code
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Boolean
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
