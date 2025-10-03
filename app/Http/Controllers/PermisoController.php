<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Modulo;
use App\Models\RoleHasPermissionModel;
use App\Models\Permiso;
use App\Models\Rol;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class PermisoController extends Controller
{
    public function index()
    {
        return Inertia::render('Security/Permisos/Index');
    }
    public function getModulosPadres()
    {
        $parent_modules = Modulo::where('modulo_padre', 0)->get();
        return response()->json(['parent_modules' => $parent_modules]);
    }

    public function save(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->all();
        if($data['role_id'] == 0){
            return response()->json(['error' => 'Debe seleccionar un rol'], 422);
        }
        RoleHasPermissionModel::where('role_id', $data['role_id'])->delete();
        $role = Role::find($data['role_id']);

        foreach ($data['permission'] as $row) {
            foreach ($row['children'] as $value) {
                foreach ($value['children'] as $action) {
                    if ($action['isChecked']) {
                        $permission = Permission::find($action['id']);
                        $role->givePermissionTo($permission);
                    }
                }

            }
        }
        DB::commit();
        return response()->json(['message' => 'Permisos guardados correctamente']);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
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


    public function destroy($id)
    {
        $modulo = Modulo::findOrFail($id);
        $modulo->delete();
        return response()->json(['message' => 'Módulo eliminado']);
    }
    public function listRole(Request $request)
    {
        $data = $request->all();
        $permissions = RoleHasPermissionModel::where('role_id', $data['role_id'])->pluck('permission_id')->toArray();
        $results = Permiso::selectRaw('permissions.*,mp.modulo_id as module_parent_id,mp.descripcion as module_parent,mc.descripcion as module_child, mc.modulo_id as module_child_id')
            ->join('modulos as mc', 'mc.modulo_id', '=', 'permissions.module_id')
            ->join('modulos as mp', 'mp.modulo_id', '=', 'mc.modulo_padre')
            ->where(function ($query) use ($data) {
                if ($data['modulo_id'] != 0) {
                    $query->where('mp.modulo_id', $data['modulo_id']);
                }
            })
            ->orderBy('permissions.id')
            ->get();

        $nestedData = [];
        $nestedData = [];

        foreach ($results as $item) {
            $parentId = $item->module_parent_id;
            $childId = $item->module_child_id;
            if (!isset($nestedData[$parentId])) {
                $nestedData[$parentId] = [
                    'id' => $parentId,
                    'title' => $item->module_parent,
                    'children' => [],
                ];
            }
            if (!isset($nestedData[$parentId]['children'][$childId])) {
                $nestedData[$parentId]['children'][$childId] = [
                    'id' => $childId,
                    'title' => $item->module_child,
                    'children' => [],
                ];
            }
            $nestedData[$parentId]['children'][$childId]['children'][] = [
                'id' => $item->id,
                'name' => $item->name,
                'title' => $item->description,
                'module_id' => $item->module_id,
                'isChecked' => in_array($item->id, $permissions),
            ];
        }
        $finalData = array_map(function ($parent) {
            $parent['children'] = array_values(array_map(function ($child) {
                $child['children'] = array_values($child['children']);
                return $child;
            }, $parent['children']));
            return $parent;
        }, array_values($nestedData));
        return response()->json($finalData);
    }
}
