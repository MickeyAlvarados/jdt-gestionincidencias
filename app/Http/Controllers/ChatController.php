<?php

namespace App\Http\Controllers;

use App\Services\AgenteIAService;
use App\Models\Chat;
use App\Models\ChatMensaje;
use App\Models\Incidencia;
use App\Models\BdConocimiento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected $agenteIAService;

    public function __construct(AgenteIAService $agenteIAService)
    {
        $this->agenteIAService = $agenteIAService;
    }

    /**
     * Crear nueva sesión de chat automáticamente
     */
    public function crearSesion(Request $request)
    {
        try {
            DB::beginTransaction();

            // Finalizar automáticamente cualquier chat anterior no finalizado del usuario
            Chat::whereHas('mensajes', function ($query) {
                $query->where('emisor', Auth::id());
            })
            ->whereIn('estado_resolucion', ['iniciado', 'esperando_feedback_bd', 'esperando_feedback_ia'])
            ->update(['estado_resolucion' => 'resuelto']);

            // Crear nuevo chat vacío (siempre uno nuevo)
            $chatId = Chat::max('id') + 1;
            $chat = Chat::create([
                'id' => $chatId,
                'user_id' => Auth::id(),
                'fecha_chat' => now(),
                'estado_resolucion' => 'iniciado',
                'intento_actual' => null,
            ]);

            DB::commit();

            Log::info('ChatController: Nuevo chat creado', [
                'chat_id' => $chat->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'chat_id' => $chat->id,
                'mensaje' => 'Chat creado correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ChatController: Error creando sesión de chat', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'mensaje' => 'Error al crear la sesión de chat'
            ], 500);
        }
    }

    /**
     * Enviar mensaje en chat existente
     */
    public function enviarMensaje(Request $request, $chatId)
    {
        $request->validate([
            'mensaje' => 'required|string|max:1000'
        ]);

        try {
            // Verificar que el chat existe
            $chat = Chat::findOrFail($chatId);

            // Guardar mensaje del usuario
            // Generar ID único para el mensaje (máximo global, no por chat)
            $mensajeId = ChatMensaje::max('id') ?? 0;
            $mensajeId++;
            ChatMensaje::create([
                'id' => $mensajeId,
                'id_chat' => $chatId,
                'emisor' => Auth::id(),
                'contenido_mensaje' => $request->input('mensaje'),
                'fecha_envio' => now(),
            ]);

            // Procesar con IA usando job
            \App\Jobs\ProcessChatMessage::dispatch($chatId, $request->input('mensaje'), Auth::id());

            return response()->json([
                'success' => true,
                'mensaje' => 'Mensaje enviado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al enviar mensaje', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'mensaje' => 'Error al enviar el mensaje'
            ], 500);
        }
    }

    /**
     * Obtener mensajes del chat
     */
    public function obtenerMensajes($chatId)
    {
        try {
            $usuarioIA = $this->getUsuarioIA();

            $mensajes = ChatMensaje::where('id_chat', $chatId)
                ->orderBy('fecha_envio', 'asc')
                ->get()
                ->map(function ($mensaje) use ($usuarioIA) {
                    $esIA = $mensaje->emisor == $usuarioIA->id;

                    return [
                        'id' => $mensaje->id,
                        'contenido' => $mensaje->contenido_mensaje,
                        'fecha_envio' => $mensaje->fecha_envio,
                        'emisor' => [
                            'id' => $mensaje->emisor,
                            'nombre' => $esIA ? 'Agente IA' : ($mensaje->emisorRelacion->name ?? 'Usuario'),
                            'es_ia' => $esIA
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'mensajes' => $mensajes
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener mensajes', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'mensaje' => 'Error al obtener mensajes'
            ], 500);
        }
    }

    /**
     * Confirmar resolución del problema con nuevo flujo
     */
    public function confirmarResolucion(Request $request, $chatId)
    {
        $request->validate([
            'resuelto' => 'required|boolean',
            'tipo_solucion' => 'required|in:bd_conocimientos,ia',
            'comentario' => 'nullable|string|max:500'
        ]);

        try {
            $chat = Chat::findOrFail($chatId);
            $resuelto = $request->input('resuelto');
            $tipoSolucion = $request->input('tipo_solucion');

            if ($resuelto) {
                // ============================================
                // CASO 1: Usuario confirma que SÍ funcionó
                // ============================================

                if ($tipoSolucion === 'ia') {
                    // Si fue IA quien resolvió, guardar en BD conocimientos (APRENDIZAJE)
                    $this->guardarSolucionExitosa($chatId, $request->input('comentario'));

                    Log::info('ChatController: Solución de IA confirmada y guardada en BD', [
                        'chat_id' => $chatId,
                        'user_id' => Auth::id()
                    ]);
                }

                // Crear/actualizar incidencia como RESUELTA
                $this->crearIncidenciaResuelta($chatId);

                // Actualizar estado del chat
                $chat->update(['estado_resolucion' => 'resuelto']);

                Log::info('ChatController: Problema resuelto confirmado', [
                    'chat_id' => $chatId,
                    'tipo_solucion' => $tipoSolucion,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => true,
                    'finalizar_chat' => true,
                    'mensaje' => $tipoSolucion === 'ia'
                        ? '¡Excelente! He aprendido de esta solución para ayudar mejor en el futuro.'
                        : '¡Problema resuelto con éxito!'
                ]);

            } else {
                // ============================================
                // CASO 2: Usuario indica que NO funcionó
                // ============================================

                if ($tipoSolucion === 'bd_conocimientos') {
                    // Primera solución (BD) no funcionó → Intentar con IA
                    $chat->update([
                        'estado_resolucion' => 'esperando_feedback_ia',
                        'intento_actual' => 'ia'
                    ]);

                    // Obtener el problema original (primer mensaje del usuario)
                    $problemaOriginal = $this->obtenerProblemaOriginal($chatId);

                    // Procesar con IA en segundo intento (flag especial)
                    \App\Jobs\ProcessChatMessage::dispatch(
                        $chatId,
                        $problemaOriginal,
                        Auth::id(),
                        'segundo_intento_ia'
                    );

                    Log::info('ChatController: Solución BD no funcionó, intentando con IA', [
                        'chat_id' => $chatId,
                        'user_id' => Auth::id()
                    ]);

                    return response()->json([
                        'success' => true,
                        'finalizar_chat' => false,
                        'mensaje' => 'Entendido. Déjame intentar con otra solución...'
                    ]);

                } else {
                    // tipoSolucion === 'ia'
                    // Segunda solución (IA) tampoco funcionó → Derivar a técnico
                    $this->derivarATecnico($chatId, $request->input('comentario'));
                    $chat->update(['estado_resolucion' => 'derivado']);

                    Log::info('ChatController: Ambas soluciones fallaron, derivando a técnico', [
                        'chat_id' => $chatId,
                        'user_id' => Auth::id()
                    ]);

                    return response()->json([
                        'success' => true,
                        'finalizar_chat' => true,
                        'mensaje' => 'He derivado tu caso a un técnico especializado que se pondrá en contacto contigo pronto.'
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('ChatController: Error al confirmar resolución', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'mensaje' => 'Error al procesar la confirmación'
            ], 500);
        }
    }

    /**
     * Guardar solución exitosa en base de conocimientos (APRENDIZAJE)
     */
    private function guardarSolucionExitosa($chatId, $comentarioUsuario = null)
    {
        try {
            // Obtener todos los mensajes del chat
            $mensajes = ChatMensaje::where('id_chat', $chatId)
                ->orderBy('fecha_envio', 'asc')
                ->get();

            // Extraer problema (primer mensaje del usuario)
            $primerMensajeUsuario = $mensajes->first(function ($mensaje) {
                return $mensaje->emisor != $this->getUsuarioIA()->id;
            });

            if (!$primerMensajeUsuario) {
                return;
            }

            $problema = $primerMensajeUsuario->contenido_mensaje;

            // Extraer solución (mensajes de la IA)
            $solucionIA = $mensajes
                ->filter(function ($mensaje) {
                    return $mensaje->emisor == $this->getUsuarioIA()->id;
                })
                ->pluck('contenido_mensaje')
                ->implode("\n\n");

            if (empty($solucionIA)) {
                return;
            }

            // Agregar comentario del usuario si existe
            if ($comentarioUsuario) {
                $solucionIA .= "\n\n**Comentario del usuario:** " . $comentarioUsuario;
            }

            // Usar el método del AgenteIAService para aprender
            $this->agenteIAService->aprenderDeSolucion(
                $chatId,
                $problema,
                $solucionIA,
                'Agente IA'
            );

            Log::info('ChatController: Solución guardada exitosamente en BD conocimientos', [
                'chat_id' => $chatId,
                'problema_length' => strlen($problema),
                'solucion_length' => strlen($solucionIA)
            ]);

        } catch (\Exception $e) {
            Log::error('ChatController: Error guardando solución en BD conocimientos', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId
            ]);
        }
    }

    /**
     * Derivar manualmente a técnico
     */
    private function derivarATecnico($chatId, $comentario = null)
    {
        try {
            $incidencia = Incidencia::where('id_chat', $chatId)->first();

            if (!$incidencia) {
                $primerMensaje = ChatMensaje::where('id_chat', $chatId)
                    ->where('emisor', Auth::id())
                    ->orderBy('fecha_envio', 'asc')
                    ->first();

                $descripcion = $primerMensaje ? $primerMensaje->contenido_mensaje : 'Problema no resuelto por IA';

                if ($comentario) {
                    $descripcion .= "\n\nComentario del usuario: " . $comentario;
                }

                $incidencia = Incidencia::create([
                    'descripcion_problema' => $descripcion,
                    'fecha_incidencia' => now(),
                    'id_chat' => $chatId,
                    'idempleado' => Auth::id(),
                    'estado' => 2, // Derivado
                    'prioridad' => 2, // Media
                ]);
            } else {
                // Actualizar incidencia existente
                $incidencia->update([
                    'estado' => 2, // Derivado
                ]);
            }

            // Asignar técnico
            $this->asignarTecnicoDisponible($incidencia);

            // Enviar mensaje de derivación en el chat
            // Generar ID único para el mensaje (máximo global, no por chat)
            $mensajeId = ChatMensaje::max('id') ?? 0;
            $mensajeId++;

            ChatMensaje::create([
                'id' => $mensajeId,
                'id_chat' => $chatId,
                'emisor' => $this->getUsuarioIA()->id,
                'contenido_mensaje' => 'He derivado tu caso a un técnico especializado. Recibirás una notificación cuando sea asignado.',
                'fecha_envio' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('ChatController: Error derivando a técnico', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId
            ]);
        }
    }

    /**
     * Obtener el problema original (primer mensaje del usuario)
     */
    private function obtenerProblemaOriginal($chatId): string
    {
        $primerMensaje = ChatMensaje::where('id_chat', $chatId)
            ->where('emisor', '!=', $this->getUsuarioIA()->id)
            ->orderBy('fecha_envio', 'asc')
            ->first();

        return $primerMensaje ? $primerMensaje->contenido_mensaje : 'Problema sin descripción';
    }

    /**
     * Crear incidencia resuelta
     */
    private function crearIncidenciaResuelta($chatId)
    {
        try {
            // Verificar si ya existe incidencia
            $incidencia = Incidencia::where('id_chat', $chatId)->first();

            if ($incidencia) {
                // Actualizar existente
                $incidencia->update(['estado' => 4]); // Resuelto

                Log::info('ChatController: Incidencia actualizada a resuelta', [
                    'incidencia_id' => $incidencia->id
                ]);
            } else {
                // Crear nueva
                $primerMensaje = ChatMensaje::where('id_chat', $chatId)
                    ->where('emisor', Auth::id())
                    ->orderBy('fecha_envio', 'asc')
                    ->first();

                $incidencia = Incidencia::create([
                    'descripcion_problema' => $primerMensaje->contenido_mensaje ?? 'Problema resuelto por IA',
                    'fecha_incidencia' => now(),
                    'id_chat' => $chatId,
                    'idempleado' => Auth::id(),
                    'estado' => 4, // Resuelto
                    'prioridad' => 1,
                ]);

                Log::info('ChatController: Nueva incidencia creada como resuelta', [
                    'incidencia_id' => $incidencia->id
                ]);
            }

            return $incidencia;

        } catch (\Exception $e) {
            Log::error('ChatController: Error creando incidencia resuelta', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId
            ]);
        }
    }

    /**
     * Utilidades
     */
    private function getUsuarioIA()
    {
        return User::where('email', 'ia@support.local')->first();
    }

    private function asignarTecnicoDisponible($incidencia)
    {
        try {
            $tecnico = User::role('TECNICO_INFORMATICA')->first();

            if ($tecnico) {
                $incidencia->update(['idempleado' => $tecnico->id]);

                Log::info('ChatController: Técnico asignado a incidencia', [
                    'incidencia_id' => $incidencia->id,
                    'tecnico_id' => $tecnico->id
                ]);
            } else {
                Log::warning('ChatController: No se encontró técnico disponible');
            }
        } catch (\Exception $e) {
            Log::error('ChatController: Error asignando técnico', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
