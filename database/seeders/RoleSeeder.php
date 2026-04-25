<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create 3 roles
        Role::create(['name' => 'opd']);
        Role::create(['name' => 'verifikator']);
        Role::create(['name' => 'monitoring']);
    }
}