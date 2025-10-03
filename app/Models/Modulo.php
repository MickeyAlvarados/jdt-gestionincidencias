<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;
    protected $table='modulos';
    protected $primaryKey = 'modulo_id';
    protected $fillable = ['descripcion','url','modulo_padre','icon','order'];
    public $timestamps = false;

}
