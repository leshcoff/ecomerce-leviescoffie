<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Administrador: entra a /admin con correo y contraseña
        $admin = User::firstOrCreate(
            ['email' => 'admin@correo.com'],
            ['name' => 'Administrador', 'password' => '12345678'],
        );
        $admin->forceFill(['is_admin' => true])->save();   // forceFill: is_admin no es "fillable"

        // Cliente de prueba: entra a la tienda con Google
        User::firstOrCreate(
            ['email' => 'leshcoff@gmail.com'],
            ['name' => 'Levi'],
        );
    }
}
