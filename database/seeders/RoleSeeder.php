<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrador',
                'slug' => Role::ADMIN,
                'description' => 'Acceso total al sistema, configuración de parámetros y gestión de usuarios.',
            ],
            [
                'name' => 'Supervisor',
                'slug' => Role::SUPERVISOR,
                'description' => 'Supervisión de tickets de su departamento, asignación y monitoreo de SLA.',
            ],
            [
                'name' => 'Técnico',
                'slug' => Role::TECHNICIAN,
                'description' => 'Resolución y diagnóstico de tickets asignados, registro de actividades.',
            ],
            [
                'name' => 'Empleado',
                'slug' => Role::EMPLOYEE,
                'description' => 'Creación y seguimiento de solicitudes de soporte técnico.',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
