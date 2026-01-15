<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table= 'categorias';
    public $timestamps = false;
    use HasFactory;


    public function nombreconcepto(){
        return $this->hasMany(NombreConcepto::class,'id_categoria');
    }
    
}
