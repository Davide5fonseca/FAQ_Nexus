<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(array_merge($credentials, ['active' => true]), false)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email ou palavra-passe incorrectos, ou conta desactivada.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.procedimentos.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('consulta')->with('status', 'Sessão terminada.');
    }
}
