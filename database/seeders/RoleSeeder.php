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
        // Primero, verificar si la tabla cargos está vacía y insertar datos si es necesario
        if (\Illuminate\Support\Facades\DB::table('cargos')->count() == 0) {
            \Illuminate\Support\Facades\DB::table('cargos')->insert([
                ['id' => 1, 'descripcion' => 'Administrador'],
                ['id' => 2, 'descripcion' => 'Logistica'],
                ['id' => 3, 'descripcion' => 'Contabilidad'],
                ['id' => 4, 'descripcion' => 'Finanzas'],
                ['id' => 5, 'descripcion' => 'Marketing'],
                ['id' => 6, 'descripcion' => 'Ventas'],
            ]);
        }

        $role1 = Role::firstOrCreate(['name' => 'ADMINISTRADOR']);
        $role2 = Role::firstOrCreate(['name' => 'AREA1']);
        $role3 = Role::firstOrCreate(['name' => 'AREA2']);
        $roleTecnico = Role::firstOrCreate(['name' => 'TECNICO_INFORMATICA']);
        $roleDocente = Role::firstOrCreate(['name' => 'DOCENTE']);

        Permission::firstOrCreate(['name' => 'modulos.index'],['description' => 'ACCEDER','module_id' => 4])->syncRoles([$role1]);
        Permission::firstOrCreate(['name' => 'modulos.store'],['description' => 'CREAR','module_id' => 4])->syncRoles([$role1,$role2]);
        Permission::firstOrCreate(['name' => 'modulos.edit'],['description' => 'EDITAR','module_id' => 4])->syncRoles([$role1,$role2]);
        Permission::firstOrCreate(['name' => 'modulos.destroy'],['description' => 'ELIMINAR','module_id' => 4])->syncRoles([$role1,$role2]);

        Permission::firstOrCreate(['name' => 'permissions.index'],['description' => 'ACCEDER','module_id' => 6])->syncRoles([$role1,$role2]);
        Permission::firstOrCreate(['name' => 'permissions.store'],['description' => 'CREAR','module_id' => 6])->syncRoles([$role1,$role2]);
        Permission::firstOrCreate(['name' => 'permissions.edit'],['description' => 'EDITAR','module_id' => 6])->syncRoles([$role1,$role2]);
        Permission::firstOrCreate(['name' => 'permissions.destroy'],['description' => 'ELIMINAR','module_id' => 6])->syncRoles([$role1,$role2]);

        Permission::firstOrCreate(['name' => 'roles.index'],['description' => 'ACCEDER','module_id' => 5])->syncRoles([$role1]);
        Permission::firstOrCreate(['name' => 'roles.store'],['description' => 'CREAR','module_id' => 5])->syncRoles([$role1]);
        Permission::firstOrCreate(['name' => 'roles.edit'],['description' => 'EDITAR','module_id' => 5])->syncRoles([$role1]);
        Permission::firstOrCreate(['name' => 'roles.destroy'],['description' => 'ELIMINAR','module_id' => 5])->syncRoles([$role1]);

        Permission::firstOrCreate(['name' => 'users.index'],['description' => 'ACCEDER','module_id' => 3])->syncRoles([$role1]);
        Permission::firstOrCreate(['name' => 'users.store'],['description' => 'CREAR','module_id' => 3])->syncRoles([$role1]);
        Permission::firstOrCreate(['name' => 'users.edit'],['description' => 'EDITAR','module_id' => 3])->syncRoles([$role1]);
        Permission::firstOrCreate(['name' => 'users.destroy'],['description' => 'ELIMINAR','module_id' => 3])->syncRoles([$role1]);

        // Crear rol para Agente IA
        $roleIA = Role::firstOrCreate(['name' => 'AGENTE_IA']);

        // Crear usuario IA
        $userIA = \App\Models\User::firstOrCreate(
            ['email' => 'ia@support.local'],
            [
                'nombres' => 'Agente IA de Soporte',
                'apellidos' => 'Automatizado',
                'password' => bcrypt('secure_password_ia_2024'),
                'email_verified_at' => now(),
            ]
        );
        $userIA->assignRole('AGENTE_IA');

        // Crear empleado para el usuario IA (necesario para la relación con chat_mensajes)
        \App\Models\Empleado::firstOrCreate(
            ['id' => $userIA->id],
            [
                'idusuarios' => $userIA->id,
                'idcargos' => 1, // Asignar cargo administrativo
            ]
        );

    }
}
