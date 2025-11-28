<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles
        $roles = [
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso total al sistema'
            ],
            [
                'nombre' => 'Secretaria',
                'descripcion' => 'Gestión administrativa y atención al cliente'
            ],
            [
                'nombre' => 'Vendedor',
                'descripcion' => 'Gestión de ventas y cotizaciones'
            ],
            [
                'nombre' => 'Técnico',
                'descripcion' => 'Gestión de órdenes de trabajo'
            ],
            [
                'nombre' => 'Contador',
                'descripcion' => 'Gestión financiera y contable'
            ],
            [
                'nombre' => 'Cliente',
                'descripcion' => 'Acceso limitado para clientes'
            ],
        ];

        foreach ($roles as $rolData) {
            Rol::firstOrCreate(
                ['nombre' => $rolData['nombre']],
                ['descripcion' => $rolData['descripcion']]
            );
        }

        // Crear usuarios de ejemplo para cada rol
        $adminRol = Rol::where('nombre', 'Administrador')->first();
        $secretariaRol = Rol::where('nombre', 'Secretaria')->first();
        $vendedorRol = Rol::where('nombre', 'Vendedor')->first();
        $tecnicoRol = Rol::where('nombre', 'Técnico')->first();
        $contadorRol = Rol::where('nombre', 'Contador')->first();
        $clienteRol = Rol::where('nombre', 'Cliente')->first();

        // Usuario Administrador
        User::firstOrCreate(
            ['email' => 'admin@sistema.com'],
            [
                'nombre' => 'Admin',
                'apellido' => 'Sistema',
                'password_hash' => Hash::make('admin123'),
                'telefono' => '70000000',
                'id_rol' => $adminRol->id_rol,
                'estado' => 'ACTIVO'
            ]
        );

        // Usuario Secretaria
        User::firstOrCreate(
            ['email' => 'secretaria@sistema.com'],
            [
                'nombre' => 'María',
                'apellido' => 'González',
                'password_hash' => Hash::make('secretaria123'),
                'telefono' => '70000001',
                'id_rol' => $secretariaRol->id_rol,
                'estado' => 'ACTIVO'
            ]
        );

        // Usuario Vendedor
        User::firstOrCreate(
            ['email' => 'vendedor@sistema.com'],
            [
                'nombre' => 'Carlos',
                'apellido' => 'Pérez',
                'password_hash' => Hash::make('vendedor123'),
                'telefono' => '70000002',
                'id_rol' => $vendedorRol->id_rol,
                'estado' => 'ACTIVO'
            ]
        );

        // Usuario Técnico
        User::firstOrCreate(
            ['email' => 'tecnico@sistema.com'],
            [
                'nombre' => 'Juan',
                'apellido' => 'Mamani',
                'password_hash' => Hash::make('tecnico123'),
                'telefono' => '70000003',
                'id_rol' => $tecnicoRol->id_rol,
                'estado' => 'ACTIVO'
            ]
        );

        // Usuario Contador
        User::firstOrCreate(
            ['email' => 'contador@sistema.com'],
            [
                'nombre' => 'Ana',
                'apellido' => 'López',
                'password_hash' => Hash::make('contador123'),
                'telefono' => '70000004',
                'id_rol' => $contadorRol->id_rol,
                'estado' => 'ACTIVO'
            ]
        );

        // Usuario Cliente
        User::firstOrCreate(
            ['email' => 'cliente@sistema.com'],
            [
                'nombre' => 'Pedro',
                'apellido' => 'Quispe',
                'password_hash' => Hash::make('cliente123'),
                'telefono' => '70000005',
                'id_rol' => $clienteRol->id_rol,
                'estado' => 'ACTIVO'
            ]
        );

        $this->command->info('Roles y usuarios de ejemplo creados correctamente!');
        $this->command->info('');
        $this->command->info('Credenciales de acceso:');
        $this->command->info('Administrador: admin@sistema.com / admin123');
        $this->command->info('Secretaria: secretaria@sistema.com / secretaria123');
        $this->command->info('Vendedor: vendedor@sistema.com / vendedor123');
        $this->command->info('Técnico: tecnico@sistema.com / tecnico123');
        $this->command->info('Contador: contador@sistema.com / contador123');
        $this->command->info('Cliente: cliente@sistema.com / cliente123');
    }
}
