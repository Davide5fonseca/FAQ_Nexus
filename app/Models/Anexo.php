<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Um ficheiro agarrado a um procedimento: uma imagem de ecrã, a fotografia de
 * uma placa, uma folha em PDF.
 *
 * O ficheiro vive fora da pasta pública (ver a migração) e só é servido pelo
 * AnexoController, que confirma antes quem pede e a que área pertence.
 */
class Anexo extends Model
{
    protected $table = 'anexos';

    protected $fillable = [
        'procedure_id', 'ficheiro', 'nome_original', 'tipo',
        'tamanho', 'legenda', 'ordem', 'criado_por',
    ];

    protected function casts(): array
    {
        return [
            'tamanho' => 'integer',
            'ordem' => 'integer',
        ];
    }

    /** O disco onde os anexos vivem — privado, fora da pasta pública. */
    public const DISCO = 'anexos';

    /** Os tipos aceites. Nada de SVG: pode trazer código lá dentro. */
    public const EXTENSOES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

    /** 10 MB por ficheiro. Chega para uma imagem de ecrã ou uma fotografia. */
    public const TAMANHO_MAXIMO_KB = 10240;

    /** Quantos anexos cabem num procedimento. */
    public const MAXIMO_POR_PROCEDIMENTO = 12;

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    public function ehImagem(): bool
    {
        return str_starts_with($this->tipo, 'image/');
    }

    /** O caminho no disco, sempre construído a partir do que gravámos. */
    public function caminho(): string
    {
        return $this->procedure_id.'/'.$this->ficheiro;
    }

    public function existeNoDisco(): bool
    {
        return Storage::disk(self::DISCO)->exists($this->caminho());
    }

    /** Ex.: "1,4 MB" */
    public function getTamanhoLegivelAttribute(): string
    {
        $kb = $this->tamanho / 1024;

        return $kb < 1024
            ? number_format($kb, 0, ',', ' ').' KB'
            : number_format($kb / 1024, 1, ',', ' ').' MB';
    }

    /** O que se mostra por baixo da imagem: a legenda, ou o nome do ficheiro. */
    public function getRotuloAttribute(): string
    {
        return $this->legenda ?: $this->nome_original;
    }

    /** Ao apagar o registo, o ficheiro sai também do disco. */
    protected static function booted(): void
    {
        static::deleting(function (self $anexo) {
            Storage::disk(self::DISCO)->delete($anexo->caminho());
        });
    }
}
