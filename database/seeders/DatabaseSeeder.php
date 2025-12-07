<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Roles y usuarios
            RoleSeeder::class,
            UserSeeder::class,
            ModuleSeeder::class,

            // Datos del sistema de chat IA
            EstadoSeeder::class,
            CategoriaSeeder::class,
            BdConocimientoSeeder::class,
            InitSeeder::class,
        ]);

    }
}
