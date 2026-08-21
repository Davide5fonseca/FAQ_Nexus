<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * As pessoas vêm do portal (tabela `utilizadores`, partilhada por toda a suite).
 * Esta aplicação não as cria nem lhes mexe: só as lê.
 *
 * O que cada pessoa pode fazer AQUI — administrador, editor ou leitor, e a área
 * a que pertence — continua a ser decidido nesta aplicação, na tabela `perfis`.
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

    /** O perfil desta pessoa nesta aplicação, lido uma só vez por pedido. */
    public function perfil(): ?Perfil
    {
        return $this->perfilEmMemoria ??= Perfil::where('utilizador_id', $this->id)->first();
    }

    private ?Perfil $perfilEmMemoria = null;

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

    public function getRoleAttribute(): string
    {
        return $this->perfil()?->papel ?? 'leitor';
    }

    public function getAreaAttribute(): ?string
    {
        return $this->perfil()?->area;
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
