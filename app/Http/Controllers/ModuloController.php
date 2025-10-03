<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerResource;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Modulo;

class ModuloController extends Controller
{

    public function index()
    {
        return Inertia::render('Security/Modulos/Index');
    }

    public function getModulosPadres()
    {
        $parent_modules = Modulo::where('modulo_padre', 0)->get();
        return response()->json(['parent_modules' => $parent_modules]);
    }

    public function store(Request $request)
    {
        $rules = [
            'descripcion' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255'],
            'order' => ['required', 'numeric'],
            'icon' => ['required', 'string', 'max:255'],
            'modulo_padre' => ['required', 'exists:modulos,modulo_id'],
        ];
        $request->validate($rules, [
            'descripcion.required' => 'El nombre es obligatorio.',
            'url.required' => 'El apellido es obligatorio.',
            'order.required' => 'El orden es obligatorio.',
            'icon.required' => 'El icono es obligatorio.',
            'modulo_padre.required' => 'El módulo padre es obligatorio.',
            'modulo_padre.exists' => 'El módulo padre no existe.',
        ]);
        DB::beginTransaction();
        try {
            $modulo = Modulo::find($request->modulo_id);
            if (is_null($modulo)) {
                $modulo = new Modulo();
            }
            $modulo->fill($request->all());
            $modulo->save();
            DB::commit();
            return response()->json(['message' => 'Módulo creado exitosamente', 'modulo' => $modulo]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar el módulo', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $module = Modulo::findOrFail($id);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'order' => 'nullable|numeric',
            'icon' => 'nullable|string|max:255',
            'parent_module' => 'nullable|numeric',
        ]);

        $module->update($validated);
        return response()->json(['message' => 'Módulo actualizado', 'module' => $module]);
    }
    public function list(Request $request)
    {
        $query = Modulo::query()->select([
            'modulo_id as id',
            'modulo_padre',
            'descripcion',
            'url',
            'order',
            'icon',
            'modulo_id',
        ])->where('modulo_padre', '!=', 0);
        // Búsqueda
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('descripcion', 'ILIKE', "%{$search}%");
                $q->orWhere('url', 'ILIKE', "%{$search}%");
            });
        }
        // Paginación
        $perPage = $request->input('perPage', 10);
        $modules = $query->orderBy('modulo_id', 'desc')->paginate($perPage);

        return response()->json($modules);
    }
    // public function list(Request $request)
    // {
    //     $porPagina = $request->input('page_size', 10);
    //     $search = $request->input('search');

    //     $query = Modulo::orderBy('module_id', 'asc');

    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('description', 'LIKE', "%$search%")
    //               ->orWhere('url', 'LIKE', "%$search%");
    //         });
    //     }

    //     $registros = $query->paginate($porPagina);
    //     return ModuleResource::collection($registros);
    // }

    // public function show(Modulo $module)
    // {
    //     return $module;
    // }

    public function destroy($id)
    {
        $modulo = Modulo::findOrFail($id);
        $modulo->delete();
        return response()->json(['message' => 'Módulo eliminado']);
    }
}
