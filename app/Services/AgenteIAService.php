<?php

namespace App\Services;

use App\Models\BdConocimiento;
use App\Models\Categoria;
use App\Models\Incidencia;
use App\Models\Chat;
use App\Models\ChatMensaje;
use Illuminate\Support\Facades\Log;

class AgenteIAService
{
    protected $deepSeekService;

    public function __construct(DeepSeekService $deepSeekService)
    {
        $this->deepSeekService = $deepSeekService;
    }

    /**
     * Procesar problema del usuario con lógica de IA inteligente
     *
     * @param string $problema Descripción del problema
     * @param array $contexto Contexto adicional (historial, usuario, etc.)
     * @param bool $forzarIA Si es true, salta búsqueda en BD y usa directamente IA
     * @return array Respuesta estructurada con solución y metadatos
     */
    public function procesarProblema(string $problema, array $contexto = [], bool $forzarIA = false): array
    {
        Log::info('AgenteIA: Procesando problema', [
            'problema' => substr($problema, 0, 100),
            'forzar_ia' => $forzarIA
        ]);

        // ================================================================
        // PASO 1: Buscar en base de conocimientos (si no es segundo intento)
        // ================================================================
        if (!$forzarIA) {
            $solucionesConocidas = $this->consultarBaseConocimientos($problema);

            if (!empty($solucionesConocidas)) {
                Log::info('AgenteIA: Solución encontrada en BD conocimientos', [
                    'cantidad_soluciones' => count($solucionesConocidas),
                    'solucion_id' => $solucionesConocidas[0]['id'] ?? null
                ]);

                return [
                    'respuesta' => $this->formatearRespuestaConocimiento($solucionesConocidas[0]),
                    'tipo_solucion' => 'bd_conocimientos',
                    'fuente' => 'base_conocimientos',
                    'categoria_detectada' => $this->detectarCategoria($problema),
                    'requiere_derivacion' => false,
                    'solucion_id' => $solucionesConocidas[0]['id'] ?? null,
                    'metadata' => [
                        'confianza' => 0.9,
                        'origen' => 'bd_conocimientos',
                        'fecha_solucion' => $solucionesConocidas[0]['fecha'] ?? null
                    ]
                ];
            }
        }

        // ================================================================
        // PASO 2: No hay solución en BD o es segundo intento → Consultar IA
        // ================================================================
        $categoria = $this->detectarCategoria($problema);
        $contextoEnriquecido = $this->enriquecerContexto($problema, $contexto, $categoria);
        $respuestaIA = $this->deepSeekService->resolverProblema($problema, $contextoEnriquecido);

        // PASO 3: Evaluar respuesta de IA
        $confianza = $this->calcularConfianza($respuestaIA);

        Log::info('AgenteIA: Respuesta generada por DeepSeek', [
            'confianza' => $confianza,
            'categoria' => $categoria,
            'longitud_respuesta' => strlen($respuestaIA['respuesta'])
        ]);

        return [
            'respuesta' => $respuestaIA['respuesta'],
            'tipo_solucion' => 'ia',
            'fuente' => 'deepseek',
            'categoria_detectada' => $categoria,
            'requiere_derivacion' => false, // Ya NO deriva automáticamente
            'metadata' => [
                'confianza' => $confianza,
                'origen' => 'deepseek',
                'categoria' => $categoria
            ]
        ];
    }

    /**
     * Consultar base de conocimientos para soluciones similares
     */
    protected function consultarBaseConocimientos(string $problema): array
    {
        try {
            $soluciones = BdConocimiento::buscarSolucionesSimilares($problema, 3);
            
            Log::info('AgenteIA: Búsqueda en BD conocimientos', [
                'resultados' => count($soluciones)
            ]);

            return $soluciones;
        } catch (\Exception $e) {
            Log::error('AgenteIA: Error consultando BD conocimientos', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Formatear respuesta desde base de conocimientos
     */
    protected function formatearRespuestaConocimiento(array $solucion): string
    {
        $respuesta = "He encontrado una solución similar en nuestra base de conocimientos:\n\n";
        $respuesta .= "**Problema similar:** {$solucion['problema']}\n\n";
        $respuesta .= "**Solución:**\n{$solucion['solucion']}\n\n";
        
        if (!empty($solucion['resolutor'])) {
            $respuesta .= "_Esta solución fue proporcionada por: {$solucion['resolutor']}_\n";
        }

        return $respuesta;
    }

    /**
     * Detectar categoría del problema
     */
    protected function detectarCategoria(string $problema): string
    {
        $problemaLower = strtolower($problema);

        // Mapeo de palabras clave a categorías
        $mapaCategorias = [
            'hardware' => ['computadora', 'pc', 'laptop', 'teclado', 'mouse', 'monitor', 'disco', 'memoria', 'cpu'],
            'red' => ['internet', 'wifi', 'red', 'conexión', 'conectar', 'ethernet', 'router', 'switch'],
            'impresora' => ['impresora', 'imprimir', 'impresión', 'toner', 'papel', 'escáner'],
            'software' => ['programa', 'aplicación', 'software', 'instalar', 'actualizar', 'error', 'sistema operativo', 'windows'],
            'correo' => ['correo', 'email', 'outlook', 'gmail', 'mensaje', 'enviar correo'],
            'acceso' => ['contraseña', 'password', 'acceso', 'login', 'usuario', 'cuenta', 'bloqueado'],
        ];

        foreach ($mapaCategorias as $categoria => $palabrasClave) {
            foreach ($palabrasClave as $palabra) {
                if (str_contains($problemaLower, $palabra)) {
                    return $categoria;
                }
            }
        }

        return 'general';
    }


    /**
     * Enriquecer contexto para DeepSeek
     */
    protected function enriquecerContexto(string $problema, array $contexto, string $categoria): array
    {
        $contextoEnriquecido = $contexto;
        $contextoEnriquecido['categoria_detectada'] = $categoria;
        $contextoEnriquecido['tipo_problema'] = $this->clasificarTipoProblema($problema);

        return $contextoEnriquecido;
    }

    /**
     * Clasificar tipo de problema
     */
    protected function clasificarTipoProblema(string $problema): string
    {
        $problemaLower = strtolower($problema);

        if (str_contains($problemaLower, 'no funciona') || str_contains($problemaLower, 'no enciende')) {
            return 'fallo_total';
        }

        if (str_contains($problemaLower, 'lento') || str_contains($problemaLower, 'demora')) {
            return 'rendimiento';
        }

        if (str_contains($problemaLower, 'error') || str_contains($problemaLower, 'mensaje')) {
            return 'error_software';
        }

        if (str_contains($problemaLower, 'cómo') || str_contains($problemaLower, 'como')) {
            return 'consulta';
        }

        return 'otro';
    }

    /**
     * Evaluar capacidad de resolución
     */
    protected function evaluarCapacidadResolucion(array $respuestaIA, string $problema): bool
    {
        $respuesta = strtolower($respuestaIA['respuesta'] ?? '');

        // Indicadores de que NO puede resolver
        $indicadoresNoResolucion = [
            'requiere intervención',
            'necesita un técnico',
            'contacta con soporte',
            'no puedo resolver',
            'requiere revisión física',
            'necesita acceso físico',
        ];

        foreach ($indicadoresNoResolucion as $indicador) {
            if (str_contains($respuesta, $indicador)) {
                return false;
            }
        }

        // Si la respuesta es muy corta, probablemente no puede resolver
        if (strlen($respuesta) < 100) {
            return false;
        }

        return true;
    }

    /**
     * Calcular nivel de confianza de la respuesta
     */
    protected function calcularConfianza(array $respuestaIA): float
    {
        $respuesta = $respuestaIA['respuesta'] ?? '';
        $confianza = 0.5; // Confianza base

        // Aumentar confianza si tiene pasos numerados
        if (preg_match('/\d+\.\s/', $respuesta)) {
            $confianza += 0.2;
        }

        // Aumentar confianza si es detallada
        if (strlen($respuesta) > 300) {
            $confianza += 0.1;
        }

        // Disminuir confianza si tiene palabras de incertidumbre
        $palabrasIncertidumbre = ['quizás', 'tal vez', 'posiblemente', 'puede que', 'no estoy seguro'];
        foreach ($palabrasIncertidumbre as $palabra) {
            if (str_contains(strtolower($respuesta), $palabra)) {
                $confianza -= 0.2;
                break;
            }
        }

        return max(0, min(1, $confianza)); // Entre 0 y 1
    }

    /**
     * Guardar solución exitosa en base de conocimientos
     */
    public function aprenderDeSolucion(int $chatId, string $problema, string $solucion, string $resolutor = 'Agente IA'): void
    {
        try {
            // Verificar si ya existe una incidencia asociada al chat
            $incidencia = Incidencia::where('id_chat', $chatId)->first();

            if (!$incidencia) {
                // Crear incidencia si no existe
                $incidencia = Incidencia::create([
                    'descripcion_problema' => $problema,
                    'fecha_incidencia' => now(),
                    'id_chat' => $chatId,
                    'estado' => 4, // Resuelto
                    'prioridad' => 1,
                ]);
            }

            // Guardar en base de conocimientos
            $incidencia->guardarEnConocimientos($solucion, $resolutor);

            Log::info('AgenteIA: Solución guardada en base de conocimientos', [
                'chat_id' => $chatId,
                'incidencia_id' => $incidencia->id
            ]);

        } catch (\Exception $e) {
            Log::error('AgenteIA: Error guardando solución en BD conocimientos', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId
            ]);
        }
    }

}
