<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = [
        'admin' => 'Administrador',
        'editor' => 'Editor',
        'leitor' => 'Leitor',
    ];

    /** Descrição de cada perfil, mostrada nos formulários. */
    public const ROLES_DESCRICAO = [
        'admin' => 'Faz tudo: procedimentos, categorias, regras de segurança, contas e eliminar.',
        'editor' => 'Cria e edita procedimentos.',
        'leitor' => 'Só consulta e imprime. Não altera nada.',
    ];

    public const AREAS = ['tecnica' => 'Área técnica', 'producao' => 'Produção'];

    protected $fillable = ['name', 'email', 'password', 'role', 'area', 'active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'admin';
    }

    /** Administradores e editores podem alterar procedimentos; leitores não. */
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
        return $this->area_label ? "{$this->name} ({$this->area_label})" : $this->name;
    }
}
