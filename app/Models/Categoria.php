<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    
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
        return $this->hasMany(Incidencia::class, 'idcategoria');
    }

    /**
     * Determinar si una categoría es crítica (requiere derivación inmediata)
     */
    public function esCritica(): bool
    {
        $categoriasCriticas = [
            'hardware',
            'red',
            'servidor',
            'seguridad',
            'base de datos',
        ];

        $descripcionLower = strtolower($this->descripcion);
        
        foreach ($categoriasCriticas as $critica) {
            if (str_contains($descripcionLower, $critica)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener nivel de prioridad según categoría
     */
    public function obtenerPrioridad(): int
    {
        $descripcionLower = strtolower($this->descripcion);

        // Prioridad Alta (3)
        if (str_contains($descripcionLower, 'hardware') || 
            str_contains($descripcionLower, 'red') ||
            str_contains($descripcionLower, 'servidor') ||
            str_contains($descripcionLower, 'seguridad')) {
            return 3;
        }

        // Prioridad Media (2)
        if (str_contains($descripcionLower, 'impresora') ||
            str_contains($descripcionLower, 'correo') ||
            str_contains($descripcionLower, 'acceso')) {
            return 2;
        }

        // Prioridad Baja (1)
        return 1;
    }
}
