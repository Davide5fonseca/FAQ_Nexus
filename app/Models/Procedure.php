<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Procedure extends Model
{
    protected $fillable = [
        'reference_number', 'title', 'problem', 'category_id', 'area',
        'ticket_notes', 'escalation', 'archived_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'reference_number' => 'integer',
        ];
    }

    // --- Relações ---

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcedureStep::class)->orderBy('position');
    }

    // --- Atributos derivados ---

    /** Referência legível, ex.: PROC-01 */
    public function getReferenceAttribute(): string
    {
        return sprintf('PROC-%02d', $this->reference_number);
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->archived_at !== null;
    }

    /** Ex.: "Área técnica" */
    public function getAreaLabelAttribute(): ?string
    {
        return $this->area ? (User::AREAS[$this->area] ?? $this->area) : null;
    }

    /**
     * Cada pessoa só vê os procedimentos da sua área.
     * Os administradores vêem os de todas as áreas.
     */
    public function scopeVisivelPara(Builder $query, ?User $user): Builder
    {
        if ($user?->is_admin) {
            return $query;
        }

        return $query->where('area', $user?->area ?? '');
    }

    /** O utilizador pode ver/editar este procedimento? */
    public function visivelPor(?User $user): bool
    {
        return (bool) $user && ($user->is_admin || $user->area === $this->area);
    }

    // --- Filtros (scopes) ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        // Escapa os caracteres especiais do LIKE (% e _) para pesquisar literalmente.
        // Usa-se "!" como carácter de escape (funciona em PostgreSQL e SQLite).
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
        $esc = "ESCAPE '!'";

        return $query->where(function (Builder $q) use ($like, $esc) {
            $q->whereRaw("LOWER(title) LIKE ? {$esc}", [$like])
                ->orWhereRaw("LOWER(COALESCE(problem, '')) LIKE ? {$esc}", [$like])
                ->orWhereRaw("LOWER(COALESCE(ticket_notes, '')) LIKE ? {$esc}", [$like])
                ->orWhereRaw("LOWER(COALESCE(escalation, '')) LIKE ? {$esc}", [$like])
                ->orWhereHas('steps', fn (Builder $s) => $s->whereRaw("LOWER(content) LIKE ? {$esc}", [$like]))
                ->orWhereHas('category', fn (Builder $c) => $c->whereRaw("LOWER(name) LIKE ? {$esc}", [$like]));
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->search($filters['q'] ?? null)
            ->when(! empty($filters['categoria']), fn (Builder $q) => $q->where('category_id', (int) $filters['categoria']));
    }

    // --- Referência automática ---

    /**
     * Devolve o próximo número de referência, de forma segura mesmo com
     * gravações em simultâneo. Os números nunca são reutilizados.
     */
    public static function nextReferenceNumber(): int
    {
        return DB::transaction(function () {
            $row = DB::table('counters')->where('name', 'procedure_reference')->lockForUpdate()->first();

            if (! $row) {
                DB::table('counters')->insert(['name' => 'procedure_reference', 'value' => 0]);
                $row = DB::table('counters')->where('name', 'procedure_reference')->lockForUpdate()->first();
            }

            $next = (int) $row->value + 1;
            DB::table('counters')->where('name', 'procedure_reference')->update(['value' => $next]);

            return $next;
        });
    }

    /** Substitui todos os passos pela lista dada (já por ordem). */
    public function syncSteps(array $contents): void
    {
        $this->steps()->delete();

        $rows = [];
        $position = 1;
        foreach ($contents as $content) {
            $content = trim((string) $content);
            if ($content === '') {
                continue;
            }
            $rows[] = ['procedure_id' => $this->id, 'position' => $position++, 'content' => $content];
        }

        if ($rows) {
            ProcedureStep::insert($rows);
        }
    }
}
