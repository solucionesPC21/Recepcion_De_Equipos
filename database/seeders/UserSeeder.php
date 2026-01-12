<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'nombre' => 'Gadiel obed Salazar',
            'usuario' => 'Gadiel77',
            'password' => bcrypt('1234567'),
        ])->assignRole('Admin');
        
        // Crear usuarios aleatorios utilizando el factory
        User::factory(1)->create();
    }
}
