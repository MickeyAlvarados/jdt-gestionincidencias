<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerResource;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Rol;

class RolController extends Controller
{

    public function index()
    {
        return Inertia::render('Security/Roles/Index');
    }

    public function store(Request $request)
    {

        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];



        $request->validate($rules, [
            'name.required' => 'El nombre es obligatorio.',
        ]);

        DB::beginTransaction();
        try {
            $request->request->add(['guard_name' => 'web']);
            $rol = Rol::find($request->id);
            if (is_null($rol)) {
                $rol = new Rol();
            }
            $rol->fill($request->all());
            $rol->save();
            DB::commit();
            return response()->json(['message' => 'Rol guardado exitosamente', 'rol' => $rol]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar el rol', 'message' => $e->getMessage()], 500);
        }
    }


    public function list(Request $request)
    {
        $query = Rol::query();
        // Búsqueda
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%");
            });
        }

        // Paginación
        $perPage = $request->input('perPage', 10);
        $users = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($users);
    }


    public function show(Rol $rol)
    {
        return $rol;
    }


    public function edit(Rol $rol) {}


    public function update(Request $request, $id)
    {
        // Obtener el módulo desde la base de datos
        $rol = Rol::findOrFail($id);
        // Actualizar el módulo con los datos del formulario
        $rol->update($request->all());

        $data = [
            'rol' => $rol,
        ];
        // Redireccionar a la vista de detalles u otra acción después de la actualización
        return response()->json($data);
    }
    public function getRoles()
    {
        $roles = Rol::all();
        return response()->json($roles);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $rol = Rol::findOrFail($id);
        $rol->delete();
        return response()->json(['message' => 'Rol eliminado con éxito!']);
    }
}
