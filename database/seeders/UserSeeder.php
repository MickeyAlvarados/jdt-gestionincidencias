<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Empleado;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Primero, verificar si la tabla cargos está vacía
        if (DB::table('cargos')->count() == 0) {
            // Insertar cargos
            DB::table('cargos')->insert([
                ['descripcion' => 'Administrador'],
                ['descripcion' => 'Logistica'],
                ['descripcion' => 'Contabilidad'],
                ['descripcion' => 'Finanzas'],
                ['descripcion' => 'Marketing'],
                ['descripcion' => 'Ventas'],
            ]);
        }

        // Verificar que el cargo existe antes de continuar
        $cargo = DB::table('cargos')->where('id', 1)->first();

        if (!$cargo) {
            throw new \Exception('El cargo con ID 1 no existe en la tabla cargos');
        }

        // Crear usuario admin y su empleado en una transacción
        DB::transaction(function () {
            $user = User::create([
                'nombres' => "admin",
                'apellidos' => "admin",
                'email' => "admin@gmail.com",
                'password' => bcrypt('123456'),
                'tipo_usuario' => 'admin',
                'role_id' => 1,
                'created_at' => Carbon::now(),
            ]);

            $user->assignRole('ADMINISTRADOR');

            Empleado::create([
                'id' => $user->id,
                'idusuarios' => $user->id,
                'idcargos' => 1, // Administrador
            ]);
        });

        // Crear otros usuarios admin y sus empleados
        for ($i = 1; $i <= 2; $i++) {
            $user = User::create([
                'nombres' => "admin{$i}",
                'apellidos' => "admin{$i}",
                'email' => "admin{$i}@gmail.com",
                'password' => bcrypt('123456'),
                'role_id' => 1,
                'created_at' => Carbon::now(),
            ]);
            $user->assignRole('ADMINISTRADOR');
            Empleado::create([
                'id' => $user->id,
                'idusuarios' => $user->id,
                'idcargos' => rand(1, 6), // Cargo aleatorio
            ]);
        }
    }
}
