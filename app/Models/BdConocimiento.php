<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BdConocimiento extends Model
{
    protected $table = 'bd_conocimientos';
    
    public $timestamps = false;
    
    protected $fillable = [
        'id',
        'id_incidencia',
        'descripcion_problema',
        'fecha_incidencia',
        'comentario_resolucion',
        'empleado_resolutor',
    ];

    protected $casts = [
        'fecha_incidencia' => 'date',
    ];

    /**
     * Relación con incidencia
     */
    public function incidencia()
    {
        return $this->belongsTo(Incidencia::class, 'id_incidencia');
    }

    /**
     * Buscar soluciones similares en la base de conocimientos
     */
    public static function buscarSolucionesSimilares(string $problema, int $limite = 5): array
    {
        // Búsqueda simple por palabras clave
        $palabrasClave = self::extraerPalabrasClave($problema);
        
        $resultados = self::where(function ($query) use ($palabrasClave) {
            foreach ($palabrasClave as $palabra) {
                $query->orWhere('descripcion_problema', 'ILIKE', "%{$palabra}%")
                      ->orWhere('comentario_resolucion', 'ILIKE', "%{$palabra}%");
            }
        })
        ->whereNotNull('comentario_resolucion')
        ->limit($limite)
        ->get();

        return $resultados->map(function ($item) {
            return [
                'problema' => $item->descripcion_problema,
                'solucion' => $item->comentario_resolucion,
                'fecha' => $item->fecha_incidencia,
                'resolutor' => $item->empleado_resolutor,
            ];
        })->toArray();
    }

    /**
     * Extraer palabras clave del problema
     */
    private static function extraerPalabrasClave(string $texto): array
    {
        // Palabras comunes a ignorar
        $stopWords = ['el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'ser', 'se', 'no', 'por', 'con', 'para', 'una', 'su', 'al', 'lo', 'como', 'más', 'pero', 'sus', 'le', 'ya', 'o', 'fue', 'este', 'ha', 'sí', 'porque', 'esta', 'son', 'entre', 'está', 'cuando', 'muy', 'sin', 'sobre', 'también', 'me', 'hasta', 'hay', 'donde', 'han', 'quien', 'están', 'estado', 'desde', 'todo', 'nos', 'durante', 'estados', 'todos', 'uno', 'les', 'ni', 'contra', 'otros', 'fueron', 'ese', 'eso', 'había', 'ante', 'ellos', 'e', 'esto', 'mí', 'antes', 'algunos', 'qué', 'unos', 'yo', 'otro', 'otras', 'otra', 'él', 'tanto', 'esa', 'estos', 'mucho', 'quienes', 'nada', 'muchos', 'cual', 'sea', 'poco', 'ella', 'estar', 'haber', 'estas', 'estaba', 'estamos', 'algunas', 'algo', 'nosotros'];
        
        // Convertir a minúsculas y extraer palabras
        $palabras = preg_split('/\s+/', strtolower($texto));
        
        // Filtrar palabras cortas y stop words
        $palabrasClave = array_filter($palabras, function ($palabra) use ($stopWords) {
            return strlen($palabra) > 3 && !in_array($palabra, $stopWords);
        });
        
        return array_values(array_unique($palabrasClave));
    }
}
