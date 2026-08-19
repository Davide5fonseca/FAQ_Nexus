<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcedureStep extends Model
{
    public $timestamps = false;

    protected $fillable = ['position', 'content'];

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }
}
