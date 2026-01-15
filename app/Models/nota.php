<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;
    protected $fillable = ['nota'];

    public function recibo()
    {
        return $this->hasOne(Recibo::class, 'id_nota');
    } 
}
