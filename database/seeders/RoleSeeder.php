<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role1=Role::create(['name' => 'ADMINISTRADOR']);
        $role2=Role::create(['name' => 'DOCENTE']);
        $role3=Role::create(['name' => 'ESTUDIANTE']);

        Permission::create(['name' => 'modulos.index','description' => 'ACCEDER','module_id' => 4])->syncRoles([$role1]);
        Permission::create(['name' => 'modulos.store','description' => 'CREAR','module_id' => 4])->syncRoles([$role1,$role2]);
        Permission::create(['name' => 'modulos.edit','description' => 'EDITAR','module_id' => 4])->syncRoles([$role1,$role2]);
        Permission::create(['name' => 'modulos.destroy','description' => 'ELIMINAR','module_id' => 4])->syncRoles([$role1,$role2]);

        Permission::create(['name' => 'permissions.index','description' => 'ACCEDER','module_id' => 6])->syncRoles([$role1,$role2]);
        Permission::create(['name' => 'permissions.store','description' => 'CREAR','module_id' => 6])->syncRoles([$role1,$role2]);
        Permission::create(['name' => 'permissions.edit','description' => 'EDITAR','module_id' => 6])->syncRoles([$role1,$role2]);
        Permission::create(['name' => 'permissions.destroy','description' => 'ELIMINAR','module_id' => 6])->syncRoles([$role1,$role2]);

        Permission::create(['name' => 'roles.index','description' => 'ACCEDER','module_id' => 5])->syncRoles([$role1]);
        Permission::create(['name' => 'roles.store','description' => 'CREAR','module_id' => 5])->syncRoles([$role1]);
        Permission::create(['name' => 'roles.edit','description' => 'EDITAR','module_id' => 5])->syncRoles([$role1]);
        Permission::create(['name' => 'roles.destroy','description' => 'ELIMINAR','module_id' => 5])->syncRoles([$role1]);

        Permission::create(['name' => 'users.index','description' => 'ACCEDER','module_id' => 3])->syncRoles([$role1]);
        Permission::create(['name' => 'users.store','description' => 'CREAR','module_id' => 3])->syncRoles([$role1]);
        Permission::create(['name' => 'users.edit','description' => 'EDITAR','module_id' => 3])->syncRoles([$role1]);
        Permission::create(['name' => 'users.destroy','description' => 'ELIMINAR','module_id' => 3])->syncRoles([$role1]);

    }
}
