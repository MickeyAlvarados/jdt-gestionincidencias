<?php

namespace Database\Seeders;

use App\Models\User;
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

        User::create([
            'nombres' => "admin",
            'apellidos' => "admin",
                'email' => "admin@gmail.com",
                'password' => bcrypt('123456'),
                'tipo_usuario' => 'admin',
                'role_id' =>1,
                'created_at' => Carbon::now(),
        ])->assignRole('ADMINISTRADOR');
        for ($i = 1; $i <= 2; $i++) {
            User::create([
                'nombres' => "admin{$i}",
                'apellidos' => "admin{$i}",
                'email' => "admin{$i}@gmail.com",
                'password' => bcrypt('123456'),
                'role_id' => 1,
                'created_at' => Carbon::now(),
            ])->assignRole('ADMINISTRADOR');
        }
    }
}
