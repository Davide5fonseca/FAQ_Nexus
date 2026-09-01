<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

/**
 * As pessoas vêm do portal (tabela `utilizadores`, partilhada por toda a suite).
 * Esta aplicação não as cria nem lhes mexe: só as lê.
 *
 * O que cada pessoa pode fazer AQUI — administrador, editor ou leitor, e a área
 * a que pertence — também vem do portal, da linha de acesso a esta aplicação
 * (`acessos.papel` e `acessos.contexto`). Durante algum tempo esteve nos dois
 * sítios, e os dois discordavam: o portal mostrava "Leitor · Área técnica" e
 * aqui a pessoa não via nada. Passou a haver um só sítio: o portal.
 *
 * As propriedades `role` e `area` mantêm os nomes que sempre tiveram, para o
 * resto do código não precisar de saber que a origem mudou.
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'utilizadores';

    /** A tabela vive na base da suite (ver config('database.suite')). */
    public function getConnectionName(): ?string
    {
        return config('database.suite');
    }

    public const ROLES = [
        'admin' => 'Administrador',
        'editor' => 'Editor',
        'leitor' => 'Leitor',
    ];

    public const ROLES_DESCRICAO = [
        'admin' => 'Faz tudo: procedimentos, categorias, regras de segurança, perfis e eliminar.',
        'editor' => 'Cria e edita procedimentos.',
        'leitor' => 'Só consulta e imprime. Não altera nada.',
    ];

    public const AREAS = ['tecnica' => 'Área técnica', 'producao' => 'Produção'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
            'password_alterada_em' => 'datetime',
        ];
    }

    /**
     * A linha de acesso desta pessoa a esta aplicação, lida uma só vez por pedido.
     *
     * Devolve null a quem o portal não deu acesso — essa pessoa nem chega aqui,
     * porque o middleware trava-a antes, mas o valor por omissão tem de ser o
     * mais restrito de todos à mesma.
     */
    public function acessoAEstaAplicacao(): ?object
    {
        if (! $this->acessoLido) {
            $this->acessoLido = true;
            $this->acessoEmMemoria = DB::connection(config('database.suite'))
                ->table('acessos')
                ->join('aplicacoes', 'aplicacoes.id', '=', 'acessos.aplicacao_id')
                ->where('aplicacoes.chave', config('app.chave'))
                ->where('acessos.utilizador_id', $this->id)
                ->select('acessos.papel', 'acessos.contexto')
                ->first();
        }

        return $this->acessoEmMemoria;
    }

    private bool $acessoLido = false;

    private ?object $acessoEmMemoria = null;

    // --- Nomes que o resto da aplicação já usava ---

    /** Nesta tabela a coluna chama-se `nome`. */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['nome'] ?? null;
    }

    /** Nesta tabela a coluna chama-se `ativo`. */
    public function getActiveAttribute(): bool
    {
        return (bool) ($this->attributes['ativo'] ?? false);
    }

    /**
     * Um valor que não reconheçamos vale sempre leitor, nunca mais do que isso:
     * lixo na base de dados não pode dar poderes a ninguém.
     */
    public function getRoleAttribute(): string
    {
        $papel = $this->acessoAEstaAplicacao()?->papel;

        return isset(self::ROLES[$papel]) ? $papel : 'leitor';
    }

    /** Sem área reconhecida não se vê procedimento nenhum, e é o que deve ser. */
    public function getAreaAttribute(): ?string
    {
        $area = $this->acessoAEstaAplicacao()?->contexto;

        return isset(self::AREAS[$area]) ? $area : null;
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'admin';
    }

    /** Administradores e editores alteram procedimentos; leitores não. */
    public function getPodeEditarAttribute(): bool
    {
        return in_array($this->role, ['admin', 'editor'], true);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function getAreaLabelAttribute(): ?string
    {
        return $this->area ? (self::AREAS[$this->area] ?? $this->area) : null;
    }

    /** Texto guardado em "criado por / alterado por", ex.: "Ana Silva (Produção)". */
    public function getSignatureAttribute(): string
    {
        return $this->area_label ? "{$this->name} ({$this->area_label})" : (string) $this->name;
    }
}
