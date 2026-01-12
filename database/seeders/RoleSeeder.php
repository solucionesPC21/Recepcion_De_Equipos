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
        $role1 = Role::create(['name' => 'Admin']);
        $role2 = Role::create(['name' => 'Caja']);

        Permission::create(['name' => 'clientes.index'])->syncRoles([$role1,$role2]);
        Permission::create(['name' => 'clientes.store'])->syncRoles([$role1,$role2]);;
        Permission::create(['name' => 'clientes.edit'])->assignRole($role1);
        Permission::create(['name' => 'clientes.destroy'])->assignRole($role1);;
        
        Permission::create(['name' => 'colonias.index'])->syncRoles([$role1,$role2]);;
        Permission::create(['name' => 'colonias.store'])->assignRole($role1);;
        Permission::create(['name' => 'colonias.edit'])->assignRole($role1);;
        Permission::create(['name' => 'colonias.destroy'])->assignRole($role1);;
    }
}
