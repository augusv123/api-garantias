<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamiliaComercial extends Model
{
    protected $connection= 'sqlsrv';
    protected $table ="fam-comerc";
    use HasFactory;
}
