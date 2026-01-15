<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NombreConcepto extends Model
{   
    use HasFactory;
    protected $table= 'nombreconcepto';

    protected $fillable = [
        'nombre',
        'precio',
        'cantidad',
        'codigo_barra',
        'marca',
        'modelo',
        'descripcion',
        'id_categoria'
    ];
    public $timestamps = false;
   

    public function categoria(){
        return $this->belongsTo(Categoria::class,'id_categoria');
    }

    public function concepto(){
        return $this->hasMany(Concepto::class,'id_nombreConcepto');
    }

    public function detallesVenta()
    {
        return $this->hasMany(VentaAbonoDetalle::class, 'nombreconcepto_id');
    }

    /**
     * Obtiene el precio formateado
     */
    public function getPrecioFormateadoAttribute()
    {
        return '$' . number_format($this->precio, 2);
    }

    public function detallesNotaVentaAbono()
    {
        return $this->hasMany(VentaDetalleNotaAbono::class, 'producto_id');
    }
}
