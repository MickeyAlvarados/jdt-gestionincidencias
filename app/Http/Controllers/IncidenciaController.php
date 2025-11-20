<?php

namespace App\Http\Controllers;

use App\Models\Cargo;
use App\Models\Incidencia;
use App\Models\DetalleIncidencia;
use App\Models\Estado;
use App\Models\Empleado;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class IncidenciaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Incidencia::with(['empleado', 'estadoRelacion', 'detalles', 'categoria', 'empleado.usuario'])
                ->orderBy('id', 'desc');

            if ($request->has('estado') && $request->estado) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('prioridad') && $request->prioridad) {
                $query->where('prioridad', $request->prioridad);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('descripcion_problema', 'like', "%{$search}%")
                        ->orWhereHas('empleado', function ($q) use ($search) {
                            $q->where('nombre', 'like', "%{$search}%");
                        });
                });
            }

            $incidencias = $query->paginate(15);
            return Inertia::render('Incidencia/Index', [
                'incidencias' => $incidencias,
                'estados' => Estado::all(),
                'categorias' => Categoria::all(),
                'empleados' => Empleado::all(),
                'cargos' => Cargo::all(),
                'prioridades' => [
                    ['id' => 3, 'nombre' => 'Alta'],
                    ['id' => 2, 'nombre' => 'Media'],
                    ['id' => 1, 'nombre' => 'Baja']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error listando incidencias', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al cargar las incidencias');
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Incidencia/Create', [
                'estados' => Estado::all(),
                'categorias' => Categoria::all(),
                'empleados' => Empleado::all(),
                'prioridades' => [
                    ['id' => 3, 'nombre' => 'Alta'],
                    ['id' => 2, 'nombre' => 'Media'],
                    ['id' => 1, 'nombre' => 'Baja']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error cargando formulario de creacion', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al cargar el formulario');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'descripcion_problema' => 'required|string|max:1000',
            'idcategoria' => 'nullable|exists:categorias,id',
            'idempleado' => 'required|exists:empleados,id',
            'prioridad' => 'required|in:1,2,3',
            'estado' => 'required|exists:estados,id',
        ]);

        try {
            DB::beginTransaction();

            $incidencia = Incidencia::create([
                'descripcion_problema' => $validated['descripcion_problema'],
                'fecha_incidencia' => now(),
                'idcategoria' => $validated['idcategoria'],
                'idempleado' => $validated['idempleado'],
                'estado' => $validated['estado'],
                'prioridad' => $validated['prioridad'],
            ]);

            DetalleIncidencia::create([
                'idincidencia' => $incidencia->id,
                'fecha_inicio' => now(),
                'estado_atencion' => $validated['estado'],
                'comentarios' => 'Incidencia creada manualmente',
            ]);

            DB::commit();



            return redirect()->route('incidencias.show', $incidencia->id)
                ->with('success', 'Incidencia creada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando incidencia', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al crear la incidencia')->withInput();
        }
    }

    public function show($id)
    {
        try {
            $incidencia = Incidencia::with([
                'empleado',
                'estadoRelacion',
                'detalles',
                'detalles.estadoAtencion',
                'detalles.cargo',
                'categoria',
                'chat',
                'empleado.usuario'
            ])->findOrFail($id);


            return Inertia::render('Incidencia/Show', [
                'incidencia' => $incidencia,
                'estados' => Estado::all(),
                'prioridades' => [
                    ['id' => 3, 'nombre' => 'Alta'],
                    ['id' => 2, 'nombre' => 'Media'],
                    ['id' => 1, 'nombre' => 'Baja']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error mostrando incidencia', ['error' => $e->getMessage()]);
            return back()->with('error', 'Incidencia no encontrada');
        }
    }

    public function edit($id)
    {
        try {
            $incidencia = Incidencia::with('detalles')->findOrFail($id);

            return Inertia::render('Incidencia/Edit', [
                'incidencia' => $incidencia,
                'estados' => Estado::all(),
                'categorias' => Categoria::all(),
                'empleados' => Empleado::all(),
                'prioridades' => [
                    ['id' => 3, 'nombre' => 'Alta'],
                    ['id' => 2, 'nombre' => 'Media'],
                    ['id' => 1, 'nombre' => 'Baja']
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error cargando formulario de edicion', ['error' => $e->getMessage()]);
            return back()->with('error', 'Incidencia no encontrada');
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'descripcion_problema' => 'required|string|max:1000',
            'idcategoria' => 'nullable|exists:categorias,id',
            'idempleado' => 'required|exists:empleados,id',
            'prioridad' => 'required|in:1,2,3',
            'estado' => 'required|exists:estados,id',
        ]);

        try {
            $incidencia = Incidencia::findOrFail($id);

            $incidencia->update([
                'descripcion_problema' => $validated['descripcion_problema'],
                'idcategoria' => $validated['idcategoria'],
                'idempleado' => $validated['idempleado'],
                'estado' => $validated['estado'],
                'prioridad' => $validated['prioridad'],
            ]);



            return redirect()->route('incidencias.show', $id)
                ->with('success', 'Incidencia actualizada correctamente');
        } catch (\Exception $e) {
            Log::error('Error actualizando incidencia', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al actualizar la incidencia')->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $incidencia = Incidencia::findOrFail($id);

            DetalleIncidencia::where('idincidencia', $id)->delete();

            $incidencia->delete();


            return redirect()->route('incidencias.index')
                ->with('success', 'Incidencia eliminada correctamente');
        } catch (\Exception $e) {
            Log::error('Error eliminando incidencia', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al eliminar la incidencia');
        }
    }

    public function crearDetalle(Request $request, $incidenciaId)
    {
        $validated = $request->validate([
            'estado_atencion' => 'required|exists:estados,id',
            'idempleado_informatica' => 'nullable|exists:empleados,id',
            'comentarios' => 'required|string|max:1000',
        ]);

        try {
            $incidencia = Incidencia::findOrFail($incidenciaId);

            $ultimoDetalle = DetalleIncidencia::where('idincidencia', $incidenciaId)
                ->max('id') ?? 0;
            $nuevoId = $ultimoDetalle + 1;

            DetalleIncidencia::create([
                'id' => $nuevoId,
                'idincidencia' => $incidenciaId,
                'fecha_inicio' => now(),
                'estado_atencion' => $validated['estado_atencion'],
                'idempleado_informatica' => $validated['idempleado_informatica'],
                'comentarios' => $validated['comentarios'],
            ]);



            return response()->json([
                'success' => true,
                'mensaje' => 'Detalle creado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error creando detalle de incidencia', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al crear el detalle'
            ], 500);
        }
    }

    public function actualizarDetalle(Request $request, $incidenciaId, $detalleId)
    {
        $validated = $request->validate([
            'estado_atencion' => 'required|exists:estados,id',
            'idempleado_informatica' => 'nullable|exists:empleados,id',
            'comentarios' => 'required|string|max:1000',
            'fecha_cierre' => 'nullable|date',
        ]);

        try {
            $detalle = DetalleIncidencia::where('idincidencia', $incidenciaId)
                ->where('id', $detalleId)
                ->firstOrFail();

            $detalle->update([
                'estado_atencion' => $validated['estado_atencion'],
                'idempleado_informatica' => $validated['idempleado_informatica'],
                'comentarios' => $validated['comentarios'],
                'fecha_cierre' => $validated['fecha_cierre'] ?? $detalle->fecha_cierre,
            ]);



            return response()->json([
                'success' => true,
                'mensaje' => 'Detalle actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error actualizando detalle de incidencia', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al actualizar el detalle'
            ], 500);
        }
    }

    public function eliminarDetalle($incidenciaId, $detalleId)
    {
        try {
            $detalle = DetalleIncidencia::where('idincidencia', $incidenciaId)
                ->where('id', $detalleId)
                ->firstOrFail();

            $detalle->delete();



            return response()->json([
                'success' => true,
                'mensaje' => 'Detalle eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error eliminando detalle de incidencia', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al eliminar el detalle'
            ], 500);
        }
    }

    public function getIncidencias(Request $request)
    {
        try {
            $query = Incidencia::with(['empleado', 'estadoRelacion', 'categoria'])
                ->orderBy('fecha_incidencia', 'desc');

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $incidencias = $query->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $incidencias
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo incidencias', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al obtener incidencias'
            ], 500);
        }
    }

    public function cambiarEstado(Request $request, $id)
    {
        $validated = $request->validate([
            'estado' => 'required|exists:estados,id',
        ]);

        try {
            $incidencia = Incidencia::findOrFail($id);
            $incidencia->update(['estado' => $validated['estado']]);



            return response()->json([
                'success' => true,
                'mensaje' => 'Estado actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error cambiando estado de incidencia', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'mensaje' => 'Error al cambiar el estado'
            ], 500);
        }
    }
    public function atender(Request $request)
    {
        $validated = $request->validate([
            'tipo_atencion' => 'required|in:resolver,derivar',
            'incidencia_id' => 'required|exists:incidencias,id',
            'descripcion_resolucion' => 'nullable|string|max:1000',
            'cargo_id' => 'nullable|exists:cargos,id',
            'descripcion_derivar' => 'nullable|string|max:1000',
        ]);
        // dd($validated);

        try {
            DB::transaction(function () use ($validated) {

                $incidencia = Incidencia::findOrFail($validated['incidencia_id']);
                if($incidencia->estado == 4){
                    throw new \Exception('La incidencia ya se encuentra resuelta');
                }

                $incidencia->update([
                    'estado' => $validated['tipo_atencion'] === 'resolver' ? 4 : 2,
                ]);


                DetalleIncidencia::create([
                    'idincidencia' => $validated['incidencia_id'],
                    'estado_atencion' => $validated['tipo_atencion'] === 'resolver' ? 4 : 2,
                    'fecha_inicio' => now(),
                    'idempleado_informatica' => Auth::id(),
                    'comentarios' =>  $validated['tipo_atencion'] === 'resolver' ? $validated['descripcion_resolucion'] : $validated['descripcion_derivar'],
                    'fecha_cierre' =>  $validated['tipo_atencion'] === 'resolver' ? now() : null,
                    'cargo_id' =>  $validated['tipo_atencion'] === 'resolver' ? null : $validated['cargo_id'],
                ]);
            });

            return response()->json([
                'success' => true,
                'mensaje' => 'Incidencia atendida correctamente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error atendiendo incidencia', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'mensaje' =>  $e->getMessage(),
            ], 500);
        }
    }
    public function historial($id)
    {
        $historial = DetalleIncidencia::where('idincidencia', $id)
            ->with([
                'cargo:id,descripcion',
                'empleadoInformatica.usuario:id,nombres,apellidos'
            ])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($detalle) {
                return [
                    'id' => $detalle->id,
                    'comentarios' => $detalle->comentarios,
                    'fecha_inicio' => $detalle->fecha_inicio,
                    'estado_atencion' => $detalle->estado_atencion,
                    'estado_text' => $detalle->estadoAtencion?->descripcion,
                    'cargo' => $detalle->cargo ? [
                        'id' => $detalle->cargo->id,
                        'descripcion' => $detalle->cargo->descripcion,
                    ] : null,
                    'empleado_informatica' => $detalle->empleadoInformatica ? [
                        'id' => $detalle->empleadoInformatica->id,
                        'usuario' => $detalle->empleadoInformatica->usuario ? [
                            'nombres' => $detalle->empleadoInformatica->usuario->nombres,
                            'apellidos' => $detalle->empleadoInformatica->usuario->apellidos,
                        ] : null,
                    ] : null,
                ];
            });

        return response()->json([
            'historial' => $historial
        ]);
    }
}
