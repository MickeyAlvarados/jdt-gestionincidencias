<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            //1
            [
                'descripcion' => 'SEGURIDAD',
                'url' => '',
                'modulo_padre' => 0,
                'icon' => 'Settings',
                'order' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            //2
            [
                'descripcion' => 'MANTENIMIENTO',
                'url' => '',
                'modulo_padre' => 0,
                'icon' => 'Package',
                'order' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            //3
            [
                'descripcion' => 'Usuarios',
                'url' => 'users.index',
                'modulo_padre' => 1,
                'icon' => 'Users',
                'order' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            //4
            [
                'descripcion' => 'Modulos',
                'url' => 'modulos.index',
                'modulo_padre' => 1,
                'icon' => 'Package',
                'order' => 3,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            
            
            //5
            [
                'descripcion' => 'Roles',
                'url' => 'roles.index',
                'modulo_padre' => 1,
                'icon' => 'Users',
                'order' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            //6
            [
                'descripcion' => 'Permisos',
                'url' => 'permissions.index',
                'modulo_padre' => 1,
                'icon' => 'Settings',
                'order' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            

        ];

        DB::table('modulos')->insert($data);
    }
}

