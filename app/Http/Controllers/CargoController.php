<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerResource;
use App\Models\User;
use App\Models\Cargo;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CargoController extends Controller
{

    public function index()
    {
        return Inertia::render('Mantenimiento/Cargo/Index');
    }
    public function update(Request $request, $id)
    {
        // Obtener el módulo desde la base de datos
        $cargo = Cargo::findOrFail($id);
        // Actualizar el módulo con los datos del formulario
        $cargo->update($request->all());

        $data = [
            'cargo' => $cargo,
        ];
        // Redireccionar a la vista de detalles u otra acción después de la actualización
        return response()->json($data);
    }
     public function store(Request $request)
    {

        $rules = [
            'descripcion' => ['required', 'string', 'max:255'],
        ];


        $request->validate($rules, [
            'descripcion.required' => 'La descripcion es obligatoria.',
        ]);

        DB::beginTransaction();
        try {
            $request->request->add(['guard_name' => 'web']);
            $cargo = Cargo::find($request->id);
            if (is_null($cargo)) {
                $cargo = new Cargo();
            }
            $cargo->descripcion = $request->descripcion;
            $cargo->save();
            DB::commit();
            return response()->json(['message' => 'Cargo guardado exitosamente', 'cargo' => $cargo]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar el cargo', 'message' => $e->getMessage()], 500);
        }
    }


    public function list(Request $request)
    {
       $query = Cargo::query();
        // Búsqueda
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('descripcion', 'ILIKE', "%{$search}%");
            });
        }

        // Paginación
        $perPage = $request->input('perPage', 10);
        $users = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($users);
    }


    public function show(User $user)
    {
    }


    public function edit(User $user) {}



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $cargo = Cargo::findOrFail($id);
        $cargo->delete();
        return response()->json(['message' => 'Cargo eliminado con éxito!']);
    }
}
