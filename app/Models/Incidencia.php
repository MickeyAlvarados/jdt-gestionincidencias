<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidencia extends Model
{
    protected $table = 'incidencias';
    
    public $timestamps = false;
    
    protected $fillable = [
        'id',
        'descripcion_problema',
        'fecha_incidencia',
        'idcategoria',
        'idempleado',
        'estado',
        'id_chat',
        'prioridad',
    ];

    protected $casts = [
        'fecha_incidencia' => 'date',
    ];

    /**
     * Relación con chat
     */
    public function chat()
    {
        return $this->belongsTo(Chat::class, 'id_chat');
    }

    /**
     * Relación con empleado (usuario que reporta)
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'idempleado');
    }

    /**
     * Relación con categoría
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'idcategoria');
    }

    /**
     * Relación con estado
     */
    public function estadoRelacion()
    {
        return $this->belongsTo(Estado::class, 'estado');
    }

    /**
     * Relación con base de conocimientos
     */
    public function conocimientos()
    {
        return $this->hasMany(BdConocimiento::class, 'id_incidencia');
    }

    /**
     * Relación con detalles de incidencia
     */
    public function detalles()
    {
        return $this->hasMany(DetalleIncidencia::class, 'idincidencia');
    }

    /**
     * Verificar si la incidencia está resuelta
     */
    public function estaResuelta(): bool
    {
        return in_array($this->estado, [Estado::RESUELTO, Estado::CERRADO]);
    }

    /**
     * Verificar si la incidencia está activa
     */
    public function estaActiva(): bool
    {
        return !in_array($this->estado, [Estado::RESUELTO, Estado::CERRADO, Estado::CANCELADO]);
    }

    /**
     * Derivar incidencia a técnico
     */
    public function derivar(): void
    {
        $this->estado = Estado::DERIVADO;
        $this->save();
    }

    /**
     * Marcar como resuelta
     */
    public function resolver(): void
    {
        $this->estado = Estado::RESUELTO;
        $this->save();
    }

    /**
     * Guardar solución en base de conocimientos
     */
    public function guardarEnConocimientos(string $solucion, string $resolutor): BdConocimiento
    {
        $conocimiento = BdConocimiento::create([
            'id_incidencia' => $this->id,
            'descripcion_problema' => $this->descripcion_problema,
            'fecha_incidencia' => $this->fecha_incidencia,
            'comentario_resolucion' => $solucion,
            'empleado_resolutor' => $resolutor,
        ]);

        // NUEVO: Generar embedding automáticamente
        try {
            $conocimiento->generarEmbedding();
            \Log::info('Embedding generado automáticamente para nuevo conocimiento', [
                'conocimiento_id' => $conocimiento->id,
                'incidencia_id' => $this->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error generando embedding para nuevo conocimiento', [
                'error' => $e->getMessage(),
                'conocimiento_id' => $conocimiento->id
            ]);
        }

        return $conocimiento;
    }
}
