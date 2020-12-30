<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garantia extends Model
{
    protected $table = "garantias";

    protected $fillable = ['orden', 'etiqueta', 'it_codigo', 'adquirido_a', 'factura', 'cuit_adquirido', 'fecha_compra', 'id_garantia', 'user_id', 'id_categoria'];

    /*
    * Relacion usuario a cual pertenece BELONGS.
    */
    public function user()
    {
    	return $this->belongsTo('App\User');
    }

    /*
    * Relacion garantia a todos sus eventos.
    */
    public function eventos(){

        return $this->hasMany('App\Evento');
    
    }

    /*
    * Relacion categoria a cual pertenece BELONGS.
    */
    public function categoria()
    {
        return $this->belongsTo('App\CategoriaGarantia');
    }
    
}
