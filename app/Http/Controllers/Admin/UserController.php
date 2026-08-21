<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Perfis desta aplicação.
 *
 * As contas são criadas no portal, e é lá que se decide quem pode abrir esta
 * aplicação. Aqui decide-se apenas o que cada pessoa faz cá dentro: se é
 * administrador, editor ou leitor, e a que área pertence.
 */
class UserController extends Controller
{
    public function index(): View
    {
        $pessoas = $this->comAcessoAEstaAplicacao();
        $perfis = Perfil::whereIn('utilizador_id', $pessoas->pluck('id'))->get()->keyBy('utilizador_id');

        return view('admin.utilizadores.index', [
            'utilizadores' => $pessoas,
            'perfis' => $perfis,
        ]);
    }

    public function edit(int $utilizador): View
    {
        $pessoa = $this->encontrar($utilizador);

        return view('admin.utilizadores.form', [
            'utilizador' => $pessoa,
            'perfil' => Perfil::where('utilizador_id', $pessoa->id)->first(),
        ]);
    }

    public function update(Request $request, int $utilizador): RedirectResponse
    {
        $pessoa = $this->encontrar($utilizador);

        $dados = $request->validate([
            'papel' => ['required', Rule::in(array_keys(User::ROLES))],
            'area' => ['required', Rule::in(array_keys(User::AREAS))],
        ], [
            'papel.in' => 'Escolha um perfil válido.',
            'area.in' => 'Escolha uma área válida.',
        ], ['papel' => 'perfil', 'area' => 'área']);

        // Não se fica sem nenhum administrador nesta aplicação.
        if ($dados['papel'] !== 'admin' && $this->ehUltimoAdministrador($pessoa->id)) {
            return back()->withInput()->withErrors([
                'papel' => 'Tem de existir pelo menos um administrador nesta aplicação.',
            ]);
        }

        Perfil::updateOrCreate(['utilizador_id' => $pessoa->id], $dados);

        return redirect()->route('admin.utilizadores.index')
            ->with('status', "Perfil de {$pessoa->name} guardado.");
    }

    /** As pessoas a quem o portal deu acesso a esta aplicação. */
    private function comAcessoAEstaAplicacao()
    {
        $ids = DB::connection(config('database.suite'))->table('acessos')
            ->join('aplicacoes', 'aplicacoes.id', '=', 'acessos.aplicacao_id')
            ->where('aplicacoes.chave', config('app.chave'))
            ->pluck('acessos.utilizador_id');

        return User::whereIn('id', $ids)->orderBy('nome')->get();
    }

    private function encontrar(int $id): User
    {
        $pessoa = $this->comAcessoAEstaAplicacao()->firstWhere('id', $id);
        abort_unless($pessoa, 404);

        return $pessoa;
    }

    private function ehUltimoAdministrador(int $id): bool
    {
        $administradores = Perfil::where('papel', 'admin')->pluck('utilizador_id');

        return $administradores->count() <= 1 && $administradores->contains($id);
    }
}
