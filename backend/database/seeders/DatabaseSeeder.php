<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Usuários do sistema (auth do dashboard) ──
        User::updateOrCreate(
            ['email' => 'admin@sistema.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin@2024'),
                'role' => UserRole::Admin,
                'is_active' => true,
                'is_approved' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'operador@sistema.com'],
            [
                'name' => 'João Operador',
                'password' => Hash::make('Operador@2024'),
                'role' => UserRole::Operator,
                'is_active' => true,
                'is_approved' => true,
            ]
        );
    }
}
