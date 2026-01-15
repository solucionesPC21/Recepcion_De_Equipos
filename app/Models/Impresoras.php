<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Impresoras extends Model
{
    use HasFactory;
    protected $fillable = [
        'nombre',
        'nombre_sistema',
        'tipo',
        'activa',
        'sumatra_path'
    ];
}
