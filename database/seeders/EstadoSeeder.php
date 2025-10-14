<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estados = [
            ['id' => 1, 'descripcion' => 'Pendiente'],
            ['id' => 2, 'descripcion' => 'Derivado'],
            ['id' => 3, 'descripcion' => 'En Proceso'],
            ['id' => 4, 'descripcion' => 'Resuelto'],
            ['id' => 5, 'descripcion' => 'Cerrado'],
            ['id' => 6, 'descripcion' => 'Cancelado'],
        ];

        foreach ($estados as $estado) {
            DB::table('estados')->updateOrInsert(
                ['id' => $estado['id']],
                $estado
            );
        }

        $this->command->info('Estados creados exitosamente.');
    }
}
