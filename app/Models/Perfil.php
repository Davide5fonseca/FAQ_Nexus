<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** O perfil de uma pessoa nesta aplicação: papel e área. */
class Perfil extends Model
{
    protected $table = 'perfis';

    protected $fillable = ['utilizador_id', 'papel', 'area'];
}
