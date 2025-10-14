<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            ['id' => 1, 'descripcion' => 'Hardware - Computadoras'],
            ['id' => 2, 'descripcion' => 'Hardware - Periféricos'],
            ['id' => 3, 'descripcion' => 'Red e Internet'],
            ['id' => 4, 'descripcion' => 'Impresoras y Escáneres'],
            ['id' => 5, 'descripcion' => 'Software - Aplicaciones'],
            ['id' => 6, 'descripcion' => 'Software - Sistema Operativo'],
            ['id' => 7, 'descripcion' => 'Correo Electrónico'],
            ['id' => 8, 'descripcion' => 'Acceso y Contraseñas'],
            ['id' => 9, 'descripcion' => 'Servidor'],
            ['id' => 10, 'descripcion' => 'Seguridad Informática'],
            ['id' => 11, 'descripcion' => 'Base de Datos'],
            ['id' => 12, 'descripcion' => 'Otros'],
        ];

        foreach ($categorias as $categoria) {
            DB::table('categorias')->updateOrInsert(
                ['id' => $categoria['id']],
                $categoria
            );
        }

        $this->command->info('Categorías creadas exitosamente.');
    }
}
