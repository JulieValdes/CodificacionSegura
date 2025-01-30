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

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:60',
            'password' => 'required|string|min:8|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'g-recaptcha-response' => 'required', //reCAPTCHA validation
        ]);
        
        if ($validator->fails()) {
            return redirect()->route('login')->withErrors($validator)->withInput();
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

        // Attempt to log in the user
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            // Verify if the user has verified their email
            if (!$user->email_verified_at) {
                Auth::logout(); 
                
                session(['email' => $user->email]);
    
                return redirect()->route('verification.notice')->with('error', 'Por favor verifica tu correo antes de iniciar sesión.');
            }

            $twoFactorCode = Str::random(6); // Generate a 2FA code
            $encryptedCode = Crypt::encrypt($twoFactorCode); // Encrypt the code

            // Save the 2FA code and expiration time in the user's record
            $user->verification_code = $encryptedCode;
            $user->verification_code_expires_at = Carbon::now()->addMinutes(10); // 10 minutes to verify
            $user->save();

            // Enviar código al correo del usuario
            Mail::to($user->email)->send(new TwoFactorCodeMail($twoFactorCode));

            // Redirigir al formulario de verificación
            return redirect()->route('verification.twoFactorForm')->with('status', 'Se ha enviado un código de verificación a tu correo.');;
        }

        // Si las credenciales son incorrectas
        return back()->withErrors(['email' => 'Estas credenciales no coinciden con nuestros registros.']);
    }

    public function showTwoFactorForm()
    {
        return view('auth.two-factor'); // Vista para ingresar el código 2FA
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'two_factor_code' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        $decryptedCode = Crypt::decrypt($user->verification_code);

        if ($decryptedCode !== $request->input('two_factor_code')) {
            return back()->withErrors(['two_factor_code' => 'Código de verificación incorrecto.']);
        }

        if (Carbon::now()->greaterThan($user->verification_code_expires_at)) {
            return back()->withErrors(['two_factor_code' => 'El código de verificación ha expirado.']);
        }

        // El código es válido, completar el inicio de sesión
        $user->verification_code = null; // Limpiar el código
        $user->verification_code_expires_at = null; // Limpiar la expiración
        $user->save();

        return redirect()->route('home'); // Redirigir al home o la página deseada
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
