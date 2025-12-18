<?php

namespace App\Jobs;

use App\Services\AgenteIAService;
use App\Models\ChatMensaje;
use App\Models\Incidencia;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatId;
    protected $mensaje;
    protected $userId;
    protected $intentoTipo;

    /**
     * Create a new job instance.
     */
    public function __construct($chatId, $mensaje, $userId, $intentoTipo = 'primer_intento')
    {
        $this->chatId = $chatId;
        $this->mensaje = $mensaje;
        $this->userId = $userId;
        $this->intentoTipo = $intentoTipo;
    }

    public function handle(AgenteIAService $agenteIAService)
    {
        try {
            Log::info('ProcessChatMessage: Iniciando procesamiento', [
                'chat_id' => $this->chatId,
                'user_id' => $this->userId,
                'intento_tipo' => $this->intentoTipo
            ]);

            // Obtener contexto del chat
            $contexto = $this->obtenerContextoChat();

            // Determinar si forzar IA (segundo intento)
            $forzarIA = ($this->intentoTipo === 'segundo_intento_ia');

            // Procesar con el Agente IA
            $respuestaIA = $agenteIAService->procesarProblema(
                $this->mensaje,
                $contexto,
                $forzarIA
            );

            // Actualizar estado del chat según tipo de solución
            $chat = \App\Models\Chat::find($this->chatId);
            if ($respuestaIA['tipo_solucion'] === 'bd_conocimientos') {
                $chat->update([
                    'estado_resolucion' => 'esperando_feedback_bd',
                    'intento_actual' => 'bd_conocimientos',
                    'solucion_propuesta_id' => $respuestaIA['solucion_id'] ?? null
                ]);
            } else {
                $chat->update([
                    'estado_resolucion' => 'esperando_feedback_ia',
                    'intento_actual' => 'ia'
                ]);
            }

            // Guardar respuesta de IA
            $mensajeId = ChatMensaje::max('id') ?? 0;
            $mensajeId++;
            $mensajeIA = ChatMensaje::create([
                'id' => $mensajeId,
                'id_chat' => $this->chatId,
                'emisor' => $this->getUsuarioIA()->id,
                'contenido_mensaje' => $respuestaIA['respuesta'],
                'fecha_envio' => now(),
            ]);

            // Broadcast del mensaje (sin toOthers para que el usuario reciba la respuesta)
            Log::info('ProcessChatMessage: Intentando broadcast', [
                'chat_id' => $this->chatId,
                'mensaje_id' => $mensajeIA->id,
                'canal' => 'chat.' . $this->chatId
            ]);

            broadcast(new MessageSent([
                'id' => $mensajeIA->id,
                'chat_id' => $this->chatId,
                'contenido' => $respuestaIA['respuesta'],
                'fecha_envio' => $mensajeIA->fecha_envio,
                'emisor' => [
                    'id' => $this->getUsuarioIA()->id,
                    'nombre' => 'Agente IA',
                    'es_ia' => true
                ],
                'metadata' => [
                    'tipo_solucion' => $respuestaIA['tipo_solucion'],
                    'fuente' => $respuestaIA['fuente'],
                    'confianza' => $respuestaIA['metadata']['confianza'] ?? 0
                ]
            ]));

            Log::info('ProcessChatMessage: Broadcast ejecutado exitosamente');

            Log::info('ProcessChatMessage: Procesamiento completado exitosamente', [
                'chat_id' => $this->chatId,
                'tipo_solucion' => $respuestaIA['tipo_solucion'],
                'fuente' => $respuestaIA['fuente']
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessChatMessage: Error procesando mensaje', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'chat_id' => $this->chatId,
                'mensaje' => $this->mensaje
            ]);

            // Mensaje de error
            $mensajeErrorId = ChatMensaje::max('id') ?? 0;
            $mensajeErrorId++;
            $mensajeError = ChatMensaje::create([
                'id' => $mensajeErrorId,
                'id_chat' => $this->chatId,
                'emisor' => $this->getUsuarioIA()->id,
                'contenido_mensaje' => 'Lo siento, ha ocurrido un error procesando tu mensaje. Un técnico se pondrá en contacto contigo pronto.',
                'fecha_envio' => now(),
            ]);

            // Broadcast del mensaje de error
            broadcast(new MessageSent([
                'id' => $mensajeError->id,
                'chat_id' => $this->chatId,
                'contenido' => $mensajeError->contenido_mensaje,
                'fecha_envio' => $mensajeError->fecha_envio,
                'emisor' => [
                    'id' => $this->getUsuarioIA()->id,
                    'nombre' => 'Agente IA',
                    'es_ia' => true
                ],
                'metadata' => [
                    'tipo_solucion' => 'error',
                    'fuente' => 'sistema'
                ]
            ]))->toOthers();

            // Crear incidencia de error
            $this->crearIncidenciaDerivada([
                'categoria_detectada' => 'error_sistema',
                'motivo_derivacion' => 'error_procesamiento'
            ]);
        }
    }

    private function obtenerContextoChat(): array
    {
        $mensajes = ChatMensaje::where('id_chat', $this->chatId)
            ->orderBy('fecha_envio', 'desc')
            ->take(10)
            ->get();

        return [
            'historial_mensajes' => $mensajes->pluck('contenido_mensaje')->toArray(),
            'usuario_id' => $this->userId,
            'cantidad_mensajes' => $mensajes->count(),
        ];
    }

    private function crearIncidenciaDerivada($respuestaIA)
    {
        try {
            // Verificar si ya existe una incidencia para este chat
            $incidenciaExistente = Incidencia::where('id_chat', $this->chatId)->first();

            if ($incidenciaExistente) {
                // Actualizar estado a derivado
                $incidenciaExistente->update([
                    'estado' => 2, // Derivado
                ]);

                Log::info('ProcessChatMessage: Incidencia existente actualizada', [
                    'incidencia_id' => $incidenciaExistente->id
                ]);

                return $incidenciaExistente;
            }

            // Crear nueva incidencia
            $incidencia = Incidencia::create([
                'descripcion_problema' => $this->mensaje,
                'fecha_incidencia' => now(),
                'id_chat' => $this->chatId,
                'idempleado' => $this->userId,
                'estado' => 2, // Derivado
                'prioridad' => 2,
            ]);

            // Asignar a técnico disponible
            $this->asignarTecnicoDisponible($incidencia);

            Log::info('ProcessChatMessage: Nueva incidencia creada', [
                'incidencia_id' => $incidencia->id,
                'categoria' => $respuestaIA['categoria_detectada'] ?? 'general'
            ]);

            return $incidencia;

        } catch (\Exception $e) {
            Log::error('ProcessChatMessage: Error creando incidencia', [
                'error' => $e->getMessage(),
                'chat_id' => $this->chatId
            ]);
        }
    }

    private function getUsuarioIA()
    {
        return User::where('email', 'ia@support.local')->first();
    }

    private function calcularPrioridad($categoria): int
    {
        $prioridades = [
            'hardware' => 3, // Alta
            'red' => 3,      // Alta
            'servidor' => 3, // Alta
            'seguridad' => 3, // Alta
            'impresora' => 2, // Media
            'software' => 1,  // Baja
            'correo' => 2,    // Media
            'acceso' => 2,    // Media
            'general' => 2,   // Media
            'error_sistema' => 3, // Alta
        ];

        return $prioridades[$categoria] ?? 2;
    }

    private function asignarTecnicoDisponible($incidencia)
    {
        try {
            // Buscar técnico con rol TECNICO_INFORMATICA
            $tecnico = User::role('TECNICO_INFORMATICA')->first();

            if ($tecnico) {
                $incidencia->update(['idempleado' => $tecnico->id]);

                Log::info('ProcessChatMessage: Técnico asignado', [
                    'incidencia_id' => $incidencia->id,
                    'tecnico_id' => $tecnico->id
                ]);
            } else {
                Log::warning('ProcessChatMessage: No se encontró técnico disponible', [
                    'incidencia_id' => $incidencia->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ProcessChatMessage: Error asignando técnico', [
                'error' => $e->getMessage(),
                'incidencia_id' => $incidencia->id
            ]);
        }
    }
}
