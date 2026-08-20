<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Recuperação e definição de palavra-passe.
 * Usado tanto pelo "Esqueci-me da palavra-passe" como pelo link do email de convite.
 */
class PasswordResetController extends Controller
{
    /** Formulário "esqueci-me da palavra-passe". */
    public function pedirForm(): View
    {
        return view('auth.recuperar');
    }

    /** Envia o email com o link de definição, sem revelar se a conta existe. */
    public function enviar(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink(['email' => mb_strtolower(trim($request->input('email')))]);

        return back()->with('status', 'Se esse email tiver conta, enviámos as instruções para definir a palavra-passe. Verifique também o spam.');
    }

    /** Formulário de definição de nova palavra-passe (aberto a partir do email). */
    public function reporForm(Request $request, string $token): View
    {
        return view('auth.repor', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /** Grava a nova palavra-passe. */
    public function repor(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:10', 'max:200', 'confirmed'],
        ], [
            'password.confirmed' => 'As palavras-passe não coincidem.',
            'password.min' => 'A palavra-passe tem de ter pelo menos 10 caracteres.',
        ], [
            'password' => 'palavra-passe',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill(['password' => $password])->save();
                // Termina sessões antigas desta conta.
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Palavra-passe definida. Já pode entrar.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
