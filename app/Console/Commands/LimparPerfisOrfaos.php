<?php

namespace App\Console\Commands;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Varre os perfis de pessoas que já não existem.
 *
 * O perfil de cada pessoa nesta aplicação (administrador/editor/leitor e a
 * área) vive aqui, mas a conta vive na base de dados do portal. São bases
 * diferentes, por isso a base de dados não consegue apagar um quando o outro
 * desaparece — quem for eliminado no portal deixa cá o perfil para trás.
 *
 * Um perfil órfão é inofensivo: ninguém o lê, porque a lista de pessoas vem
 * sempre de quem tem acesso a esta aplicação. Mas é lixo, e ao fim de uns anos
 * é lixo a mais.
 */
class LimparPerfisOrfaos extends Command
{
    protected $signature = 'perfis:limpar {--mostrar : Só mostra o que apagaria, sem apagar}';

    protected $description = 'Apaga os perfis de pessoas que já não existem na lista partilhada';

    public function handle(): int
    {
        $existentes = User::query()->pluck('id');
        $orfaos = Perfil::query()->whereNotIn('utilizador_id', $existentes)->get();

        if ($orfaos->isEmpty()) {
            $this->info('Não há perfis órfãos.');

            return self::SUCCESS;
        }

        $this->table(
            ['perfil', 'nº da pessoa', 'papel', 'área'],
            $orfaos->map(fn (Perfil $p) => [$p->id, $p->utilizador_id, $p->papel, $p->area])->all()
        );

        if ($this->option('mostrar')) {
            $this->comment('Nada foi apagado (--mostrar).');

            return self::SUCCESS;
        }

        $quantos = $orfaos->count();
        Perfil::whereIn('id', $orfaos->pluck('id'))->delete();

        $this->info("{$quantos} ".($quantos === 1 ? 'perfil apagado' : 'perfis apagados').'.');

        return self::SUCCESS;
    }
}
