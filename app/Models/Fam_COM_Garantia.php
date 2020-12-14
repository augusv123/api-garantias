<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fam_COM_Garantia extends Model
{
    use HasFactory;
    protected $connection= 'sqlsrv';
    protected $table ="fam_com_garantias";
    public $timestamps = false;
    public function categoria(){
        return $this->hasOne('App\Models\CategoriaDeGarantia', 'id','cat_garantia');
    }
    public function familiaComercial(){
        return $this->hasOne('App\Models\FamiliaComercial', 'fm-cod-com','fam_com');
    }

}
