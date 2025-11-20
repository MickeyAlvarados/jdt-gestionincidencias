<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('incidencias')->insert([
            [
                'id' => 1,
                'descripcion_problema' => 'No enciende la computadora',
                'fecha_incidencia' => '2025-01-19',
                'idcategoria' => 1,
                'idempleado' => 1,
                'estado' => 2,
                'id_chat' => null,
                'prioridad' => 2,
            ],
            [
                'id' => 2,
                'descripcion_problema' => 'Problema con el sistema de ventas',
                'fecha_incidencia' => '2025-01-19',
                'idcategoria' => 2,
                'idempleado' => 1,
                'estado' => 2,
                'id_chat' => null,
                'prioridad' => 1,
            ],
        ]);
        DB::table('detalle_incidencia')->insert([
            [

                'idincidencia' => 1,
                'fecha_inicio' => '2025-01-19',
                'estado_atencion' => 2,
                'idempleado_informatica' => 1,
                'comentarios' => 'Se revisó el equipo, posible falla de fuente.',
                'fecha_cierre' => null,
            ],
            [
                'idincidencia' => 2,
                'fecha_inicio' => '2025-01-19',
                'estado_atencion' => 2,
                'idempleado_informatica' => 1,
                'comentarios' => 'Se reinició el servidor.',
                'fecha_cierre' => '2025-01-19',
            ],
        ]);
    }
}
