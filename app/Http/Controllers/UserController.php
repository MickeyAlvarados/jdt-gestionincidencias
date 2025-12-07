<?php

namespace App\Http\Controllers;

use App\Http\Resources\CustomerResource;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function index()
    {
        return Inertia::render('Security/Users/Index');
    }
    public function getCustomers()
    {
        $customers = User::where('customer_id', 0)->get();
        $data = [
            'customer_id' => $customers,
        ];
        return response()->json($data);
    }

    public function store(Request $request)
    {
        $rules = [
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $request->id],
            'role_id' => ['required', 'exists:roles,id'],
        ];

        // Solo valida password cuando id == -1 (creación)
        if ($request->id == -1) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
            $rules['password_confirmation'] = ['required', 'string', 'min:8'];
        }

        $request->validate($rules, [
            'nombres.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'El apellido es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.unique' => 'El correo ya existe.',
            'email.email' => 'El correo debe ser un correo electronico valido.',
            'role_id.required' => 'El perfil es obligatorio.',
            'role_id.exists' => 'El perfil no existe.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password_confirmation.required' => 'La confirmacion de la contraseña es obligatoria.',
            'password_confirmation.min' => 'La confirmacion de la contraseña debe tener al menos 8 caracteres.',
        ]);
        DB::beginTransaction();
        try {
            $user = User::find($request->id);
            if (is_null($user)) {
                $user = new User();
            } else {
                $request->request->remove('password');
                $request->request->remove('password_confirmation');
            }
            $user->fill($request->all());
            $user->save();
            DB::commit();
            return response()->json(['message' => 'Usuario guardado exitosamente', 'user' => $user]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar el usuario', 'message' => $e->getMessage()], 500);
        }
    }


    public function list(Request $request)
    {
        $query = User::query()->join('roles', 'roles.id', '=', 'users.role_id')
        ->select('users.*', 'roles.name as role_name');
        // Búsqueda
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('users.nombres', 'ILIKE', "%{$search}%")
                  ->orWhere('users.apellidos', 'ILIKE', "%{$search}%")
                  ->orWhere('users.codigo', 'ILIKE', "%{$search}%")
                  ->orWhere('users.email', 'ILIKE', "%{$search}%")
                  ->orWhere('roles.name', 'ILIKE', "%{$search}%");
            });
        }

        // Paginación
        $perPage = $request->input('perPage', 10);
        $users = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($users);
    }


    public function show(User $user)
    {
        return $user;
    }


    public function edit(User $user) {}


    public function update(Request $request, $id)
    {
        // Obtener el módulo desde la base de datos
        $user = User::findOrFail($id);
        // Actualizar el módulo con los datos del formulario
        $user->update($request->all());

        $data = [
            'user' => $user,
        ];
        // Redireccionar a la vista de detalles u otra acción después de la actualización
        return response()->json($data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'Usuario eliminado con éxito!']);
    }
}
