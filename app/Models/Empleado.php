<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'id',
        'idcargos',
        'idusuarios'
    ];

    protected $casts = [];

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'int';

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuarios', 'id');
    }
}
