<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleIncidencia extends Model
{
    protected $table = 'detalle_incidencia';

    public $timestamps = false;

    // Clave primaria compuesta
    protected $primaryKey = ['id', 'idincidencia'];
    public $incrementing = false;

    protected $fillable = [
        'id',
        'idincidencia',
        'fecha_inicio',
        'estado_atencion',
        'idempleado_informatica',
        'comentarios',
        'fecha_cierre',
        'cargo_id',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_cierre' => 'date',
    ];

    /**
     * Relación con incidencia
     */
    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }
    public function incidencia()
    {
        return $this->belongsTo(Incidencia::class, 'idincidencia');
    }

    /**
     * Relación con estado de atención
     */
    public function estadoAtencion()
    {
        return $this->belongsTo(Estado::class, 'estado_atencion');
    }

    /**
     * Relación con empleado de informática (técnico)
     */
    public function empleadoInformatica()
    {
        return $this->belongsTo(Empleado::class, 'idempleado_informatica');
    }

    /**
     * Verificar si el detalle está resuelto
     */
    public function estaResuelto(): bool
    {
        return in_array($this->estado_atencion, [Estado::RESUELTO, Estado::CERRADO]);
    }

    /**
     * Marcar como resuelto
     */
    public function resolver(string $comentarios = null): void
    {
        $this->estado_atencion = Estado::RESUELTO;
        $this->fecha_cierre = now();

        if ($comentarios) {
            $this->comentarios = $comentarios;
        }

        $this->save();
    }
}
