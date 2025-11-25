<?php

namespace App\Http\Controllers;

use App\Models\Incidencia;
use App\Models\Estado;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Estadísticas generales
        $totalIncidencias = Incidencia::count();
        $incidenciasPendientes = Incidencia::where('estado', Estado::PENDIENTE)->count();
        $incidenciasResueltas = Incidencia::where('estado', Estado::RESUELTO)->count();
        $incidenciasEnProceso = Incidencia::whereIn('estado', [Estado::DERIVADO, Estado::EN_PROCESO])->count();

        // Porcentaje de resolución
        $porcentajeResolucion = $totalIncidencias > 0
            ? round(($incidenciasResueltas / $totalIncidencias) * 100, 2)
            : 0;

        // Incidencias por estado
        $incidenciasPorEstado = Incidencia::select('estados.descripcion', DB::raw('count(*) as total'))
            ->join('estados', 'incidencias.estado', '=', 'estados.id')
            ->groupBy('estados.id', 'estados.descripcion')
            ->get()
            ->map(function ($item) {
                return [
                    'estado' => $item->descripcion,
                    'total' => $item->total
                ];
            });

        // Incidencias por prioridad
        $incidenciasPorPrioridad = Incidencia::select('prioridad', DB::raw('count(*) as total'))
            ->groupBy('prioridad')
            ->get()
            ->map(function ($item) {
                $prioridades = [1 => 'Baja', 2 => 'Media', 3 => 'Alta'];
                return [
                    'prioridad' => $prioridades[$item->prioridad] ?? 'Desconocida',
                    'total' => $item->total
                ];
            });

        // Incidencias por categoría (top 5)
        $incidenciasPorCategoria = Incidencia::select('categorias.descripcion', DB::raw('count(*) as total'))
            ->leftJoin('categorias', 'incidencias.idcategoria', '=', 'categorias.id')
            ->groupBy('categorias.id', 'categorias.descripcion')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'categoria' => $item->descripcion ?? 'Sin categoría',
                    'total' => $item->total
                ];
            });

        // Tendencia últimos 7 días
        $tendenciaIncidencias = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::now()->subDays($i);
            $count = Incidencia::whereDate('fecha_incidencia', $fecha->format('Y-m-d'))->count();
            $tendenciaIncidencias[] = [
                'fecha' => $fecha->format('d/m'),
                'total' => $count
            ];
        }

        // 🔥 Tiempo promedio de resolución (fix PostgreSQL)
        $tiempoPromedioResolucion = DB::table('incidencias')
            ->join('detalle_incidencia', 'incidencias.id', '=', 'detalle_incidencia.idincidencia')
            ->where('incidencias.estado', Estado::RESUELTO)
            ->whereNotNull('detalle_incidencia.fecha_cierre')
            ->select(DB::raw(
                'AVG(EXTRACT(EPOCH FROM (detalle_incidencia.fecha_cierre::timestamp - incidencias.fecha_incidencia::timestamp)) / 86400) AS promedio'
            ))
            ->value('promedio');

        $tiempoPromedioResolucion = $tiempoPromedioResolucion ? round($tiempoPromedioResolucion, 1) : 0;

        // Incidencias recientes
        $incidenciasRecientes = Incidencia::with(['empleado.usuario', 'estadoRelacion', 'categoria'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($incidencia) {
                return [
                    'id' => $incidencia->id,
                    'descripcion' => $incidencia->descripcion_problema,
                    'usuario' => $incidencia->empleado?->usuario
                        ? $incidencia->empleado->usuario->nombres . ' ' . $incidencia->empleado->usuario->apellidos
                        : 'N/A',
                    'estado' => $incidencia->estadoRelacion?->descripcion ?? 'N/A',
                    'categoria' => $incidencia->categoria?->descripcion ?? 'Sin categoría',
                    'prioridad' => $incidencia->prioridad,
                    'fecha' => $incidencia->fecha_incidencia->format('d/m/Y')
                ];
            });

        // Áreas con más incidencias (top 5)
        $incidenciasPorArea = DB::table('incidencias')
            ->join('detalle_incidencia', 'incidencias.id', '=', 'detalle_incidencia.idincidencia')
            ->join('roles', 'detalle_incidencia.role_id', '=', 'roles.id')
            ->select('roles.name as area', DB::raw('count(distinct incidencias.id) as total'))
            ->groupBy('roles.id', 'roles.name')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'area' => $item->area,
                    'total' => $item->total
                ];
            });

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalIncidencias' => $totalIncidencias,
                'incidenciasPendientes' => $incidenciasPendientes,
                'incidenciasResueltas' => $incidenciasResueltas,
                'incidenciasEnProceso' => $incidenciasEnProceso,
                'porcentajeResolucion' => $porcentajeResolucion,
                'tiempoPromedioResolucion' => $tiempoPromedioResolucion
            ],
            'charts' => [
                'incidenciasPorEstado' => $incidenciasPorEstado,
                'incidenciasPorPrioridad' => $incidenciasPorPrioridad,
                'incidenciasPorCategoria' => $incidenciasPorCategoria,
                'tendenciaIncidencias' => $tendenciaIncidencias,
                'incidenciasPorArea' => $incidenciasPorArea
            ],
            'incidenciasRecientes' => $incidenciasRecientes
        ]);
    }
}
