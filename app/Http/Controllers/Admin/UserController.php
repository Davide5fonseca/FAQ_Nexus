<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\DefinirPalavraPasse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Gestão de contas (só administradores). */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.utilizadores.index', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.utilizadores.form', ['user' => new User(['role' => 'editor', 'active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            // Palavra-passe provisória aleatória, que ninguém conhece;
            // a pessoa define a sua através do link enviado por email.
            'password' => Str::random(40),
            'role' => $data['role'],
            'area' => $data['area'],
            'active' => true,
        ]);

        $this->enviarConvite($user);

        return redirect()->route('admin.utilizadores.index')
            ->with('status', "Conta de {$user->name} criada. Foi enviado um email para {$user->email} com o link para definir a palavra-passe.");
    }

    /** Reenvia o email com o link para definir a palavra-passe. */
    public function convite(User $user): RedirectResponse
    {
        $this->enviarConvite($user);

        return back()->with('status', "Email para definir a palavra-passe reenviado para {$user->email}.");
    }

    private function enviarConvite(User $user): void
    {
        $user->notify(new DefinirPalavraPasse(Password::createToken($user)));
    }

    public function edit(User $user): View
    {
        return view('admin.utilizadores.form', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validateData($request, $user);
        $self = $request->user()->is($user);

        if ($self && ($data['role'] !== 'admin' || ! $request->boolean('active'))) {
            return back()->withInput()->withErrors(['role' => 'Não pode retirar o seu próprio perfil de administrador nem desactivar a sua própria conta.']);
        }

        if ($user->is_admin && ($data['role'] !== 'admin' || ! $request->boolean('active')) && User::where('role', 'admin')->where('active', true)->count() <= 1) {
            return back()->withInput()->withErrors(['role' => 'Tem de existir pelo menos um administrador activo.']);
        }

        $user->fill([
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'role' => $data['role'],
            'area' => $data['area'],
            'active' => $request->boolean('active'),
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $user->save();

        // Se a conta foi desactivada ou a palavra-passe mudou, termina as sessões
        // dessa pessoa e invalida o "manter sessão iniciada".
        if (! $user->active || filled($data['password'] ?? null)) {
            $user->forceFill(['remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return redirect()->route('admin.utilizadores.index')->with('status', "Conta de {$user->name} guardada.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'Não pode eliminar a sua própria conta.']);
        }
        if ($user->is_admin && User::where('role', 'admin')->where('active', true)->count() <= 1) {
            return back()->withErrors(['user' => 'Tem de existir pelo menos um administrador activo.']);
        }

        $name = $user->name;
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->delete();

        return redirect()->route('admin.utilizadores.index')
            ->with('status', "Conta de {$name} eliminada. Os procedimentos que criou mantêm-se, com o nome registado.");
    }

    private function validateData(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'area' => ['required', Rule::in(array_keys(User::AREAS))],
            'password' => ['nullable', 'string', 'min:10', 'max:200'],
        ], [
            'email.unique' => 'Já existe uma conta com esse email.',
            'password.min' => 'A palavra-passe tem de ter pelo menos 10 caracteres.',
            'role.in' => 'Escolha um perfil válido.',
            'area.in' => 'Escolha uma área válida.',
        ], [
            'name' => 'nome', 'email' => 'email', 'role' => 'perfil', 'area' => 'área', 'password' => 'palavra-passe',
        ]);
    }
}
