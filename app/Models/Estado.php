<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    protected $table = 'estados';
    
    public $timestamps = false;
    
    protected $fillable = [
        'id',
        'descripcion',
    ];

    /**
     * Relación con incidencias
     */
    public function incidencias()
    {
        return $this->hasMany(Incidencia::class, 'estado');
    }

    /**
     * Estados predefinidos del sistema
     */
    const PENDIENTE = 1;
    const DERIVADO = 2;
    const EN_PROCESO = 3;
    const RESUELTO = 4;
    const CERRADO = 5;
    const CANCELADO = 6;

    /**
     * Verificar si el estado es final
     */
    public function esFinal(): bool
    {
        return in_array($this->id, [self::RESUELTO, self::CERRADO, self::CANCELADO]);
    }

    /**
     * Verificar si el estado permite modificaciones
     */
    public function permiteModificaciones(): bool
    {
        return !$this->esFinal();
    }
}
