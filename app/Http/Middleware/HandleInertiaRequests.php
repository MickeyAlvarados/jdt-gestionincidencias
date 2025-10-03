<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use App\Models\RoleHasPermissionModel;
class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $user = $request->user();
        $list_module = [];
        $permissions = [];
        if ($user) {
            $list_module = RoleHasPermissionModel::join('roles as r', 'r.id', '=', 'role_has_permissions.role_id')
                ->join('permissions as p', 'p.id', '=', 'role_has_permissions.permission_id')
                ->join('modulos as mc', 'p.module_id', '=', 'mc.modulo_id')
                ->join('modulos as mp', 'mp.modulo_id', '=', 'mc.modulo_padre')
                ->whereColumn('mc.url', '=', 'p.name')
                ->where('role_id', '=', $user->role_id)
                ->select(
                    'mp.modulo_id as module_parent_id',
                    'mp.descripcion as module_parent',
                    'mp.icon',
                    'mc.descripcion as module_child',
                    'mc.url',
                    'permission_id',
                    'role_id',
                    'p.name',
                    'p.name as permissions_name',
                    'r.name as role_name'
                )
                ->get();

            $permissions = RoleHasPermissionModel::where('role_id', $user->role_id)
                ->join('permissions as p', 'p.id', '=', 'role_has_permissions.permission_id')
                ->select('p.name as permission')
                ->get();
        }

        $modules = [];
        foreach ($list_module as $item) {
            $parentKey = $item->module_parent_id;
            if (!isset($modules[$parentKey])) {
                $modules[$parentKey] = (object)[
                    'title' => $item->module_parent,
                    'href' => $item->url, // Usar URL del módulo padre si existe, sino el nombre
                    'icon' =>  $item->icon,
                    'subItems' => [],
                ];
            }
            $modules[$parentKey]->subItems[] = (object)[
                'title' => $item->module_child,
                'href' => $item->url, // Usar URL real del módulo hijo
            ];
        }
        $modules = array_values($modules);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'modules' => $modules,
                'list_module' => $list_module,
                'permissions'=>$permissions,
            ],
            'ziggy' => [
                ...(new Ziggy(null, $request->url()))->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
