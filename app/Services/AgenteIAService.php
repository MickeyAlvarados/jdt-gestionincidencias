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

        // PASO 1: Buscar en base de conocimientos
        $solucionesConocidas = [];
        if (!$forzarIA) {
            $solucionesConocidas = $this->consultarBaseConocimientos($problema);
        }

        // PASO 2: Determinar estrategia
        $categoria = $this->detectarCategoria($problema);

        if (!empty($solucionesConocidas) && !$forzarIA) {
            // Hay soluciones relevantes → Enviar a DeepSeek con contexto de conversaciones previas
            Log::info('AgenteIA: Soluciones encontradas en BD, enviando a IA', [
                'cantidad_soluciones' => count($solucionesConocidas)
            ]);

            $contextoEnriquecido = $this->enriquecerContexto($problema, $contexto, $categoria);
            $contextoEnriquecido['conversaciones_previas'] = $solucionesConocidas;

            $respuestaIA = $this->deepSeekService->resolverProblema($problema, $contextoEnriquecido);

            return [
                'respuesta' => $respuestaIA['respuesta'],
                'tipo_solucion' => 'ia_con_conocimientos',
                'fuente' => 'deepseek_con_bd',
                'categoria_detectada' => $categoria,
                'requiere_derivacion' => false,
                'metadata' => [
                    'confianza' => $respuestaIA['confianza'] ?? 0.9,
                    'origen' => 'deepseek_bd',
                    'categoria' => $categoria,
                    'soluciones_usadas' => count($solucionesConocidas)
                ]
            ];
        }

        // PASO 3: No hay soluciones o segundo intento → IA pura
        $contextoEnriquecido = $this->enriquecerContexto($problema, $contexto, $categoria);
        $respuestaIA = $this->deepSeekService->resolverProblema($problema, $contextoEnriquecido);

        return [
            'respuesta' => $respuestaIA['respuesta'],
            'tipo_solucion' => 'ia',
            'fuente' => 'deepseek',
            'categoria_detectada' => $categoria,
            'requiere_derivacion' => false,
            'metadata' => [
                'confianza' => $this->calcularConfianza($respuestaIA),
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
            // Búsqueda por palabras clave (top 3)
            $soluciones = BdConocimiento::buscarSolucionesSimilares($problema, 3);

            Log::info('AgenteIA: Búsqueda en BD conocimientos', [
                'total_resultados' => count($soluciones)
            ]);

            return $soluciones;

        } catch (\Exception $e) {
            Log::error('AgenteIA: Error en búsqueda BD', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
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
