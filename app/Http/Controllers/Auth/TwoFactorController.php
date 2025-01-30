<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function showTwoFactorForm()
    {
        return view('auth.2fa');
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric',
        ]);

        $code = Cache::get('2fa_code_' . Auth::id());

        if ($code && $code == $request->code) {
            Cache::forget('2fa_code_' . Auth::id());
            return redirect()->intended('dashboard');
        }

        return back()->withErrors(['code' => 'Invalid 2FA code.']);
    }
}
