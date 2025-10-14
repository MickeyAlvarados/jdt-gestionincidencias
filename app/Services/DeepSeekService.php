<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected $client;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key');
        $this->baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com/v1');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    /**
     * Resolver problema de soporte informático usando DeepSeek
     */
    public function resolverProblema(string $problema, array $contexto = []): array
    {
        try {
            // Construir prompt especializado para soporte IT
            $prompt = $this->construirPromptSoporte($problema, $contexto);

            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Eres un asistente especializado en soporte informático. Proporciona soluciones claras, paso a paso, para problemas comunes de computadoras, impresoras, software y hardware. Si no puedes resolver el problema, indica claramente que requiere intervención técnica especializada.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 1000,
                    'temperature' => 0.3,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $respuesta = $data['choices'][0]['message']['content'] ?? '';

            // Determinar si puede resolver o necesita derivación
            $puedeResolver = $this->evaluarCapacidadResolucion($respuesta);

            return [
                'respuesta' => $respuesta,
                'puede_resolver' => $puedeResolver,
                'confianza' => $this->calcularConfianza($respuesta),
                'categoria_detectada' => $this->detectarCategoria($problema),
            ];

        } catch (RequestException $e) {
            Log::error('Error en DeepSeek API', [
                'error' => $e->getMessage(),
                'problema' => $problema
            ]);

            return [
                'respuesta' => 'Lo siento, actualmente no puedo procesar tu solicitud. Por favor, contacta directamente con el soporte técnico.',
                'puede_resolver' => false,
                'confianza' => 0,
                'categoria_detectada' => 'error_sistema',
            ];
        }
    }

    /**
     * Construir prompt optimizado para soporte IT
     */
    private function construirPromptSoporte(string $problema, array $contexto): string
    {
        $basePrompt = "Problema reportado: {$problema}\n\n";

        if (!empty($contexto)) {
            $basePrompt .= "Información adicional:\n";
            foreach ($contexto as $key => $value) {
                $basePrompt .= "- {$key}: {$value}\n";
            }
            $basePrompt .= "\n";
        }

        $basePrompt .= "Por favor, proporciona una solución paso a paso. Si el problema requiere intervención física o conocimientos especializados avanzados, indícalo claramente.";

        return $basePrompt;
    }

    /**
     * Evaluar si la IA puede resolver el problema
     */
    private function evaluarCapacidadResolucion(string $respuesta): bool
    {
        $indicadoresDerivacion = [
            'requiere intervención técnica',
            'contactar con soporte',
            'necesita técnico especializado',
            'no puedo resolver',
            'intervención física',
            'reparación hardware',
            'problema complejo'
        ];

        $respuestaLower = strtolower($respuesta);

        foreach ($indicadoresDerivacion as $indicador) {
            if (str_contains($respuestaLower, $indicador)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcular nivel de confianza en la respuesta
     */
    private function calcularConfianza(string $respuesta): float
    {
        $puntuacion = 0;

        // Longitud adecuada de respuesta
        if (strlen($respuesta) > 100) $puntuacion += 0.3;

        // Contiene pasos numerados
        if (preg_match('/\d+\./', $respuesta)) $puntuacion += 0.2;

        // Menciona soluciones específicas
        if (preg_match('/reiniciar|actualizar|configurar|verificar/i', $respuesta)) $puntuacion += 0.2;

        // No contiene frases de duda
        if (!preg_match('/podría|tal vez|posiblemente/i', $respuesta)) $puntuacion += 0.2;

        // Contiene instrucciones claras
        if (str_contains($respuesta, 'paso') || str_contains($respuesta, '1.')) $puntuacion += 0.1;

        return min(1.0, $puntuacion);
    }

    /**
     * Detectar categoría del problema
     */
    private function detectarCategoria(string $problema): string
    {
        $problemaLower = strtolower($problema);

        if (str_contains($problemaLower, 'impresora') || str_contains($problemaLower, 'imprimir')) {
            return 'impresora';
        }

        if (str_contains($problemaLower, 'internet') || str_contains($problemaLower, 'conexión') || str_contains($problemaLower, 'red')) {
            return 'red';
        }

        if (str_contains($problemaLower, 'pantalla') || str_contains($problemaLower, 'monitor') || str_contains($problemaLower, 'display')) {
            return 'hardware';
        }

        if (str_contains($problemaLower, 'office') || str_contains($problemaLower, 'word') || str_contains($problemaLower, 'excel')) {
            return 'software';
        }

        return 'general';
    }

    /**
     * Obtener soluciones similares de la base de conocimiento
     */
    public function buscarSolucionesSimilares(string $problema): array
    {
        // Aquí se implementaría búsqueda en bd_conocimientos
        // Por ahora retornamos array vacío
        return [];
    }
}
