<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SafetyRule extends Model
{
    protected $fillable = ['position', 'content', 'updated_by'];
}
