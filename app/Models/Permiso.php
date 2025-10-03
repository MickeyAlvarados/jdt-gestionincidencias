<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Permiso extends Model
{
    use HasFactory;
    protected $table='permissions';
    protected $primaryKey='id';
    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'module_id',
    ];
    public $timestamps = true;
}
