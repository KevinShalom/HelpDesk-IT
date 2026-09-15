<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('slug', Role::ADMIN)->first();
        $supervisorRole = Role::where('slug', Role::SUPERVISOR)->first();
        $technicianRole = Role::where('slug', Role::TECHNICIAN)->first();
        $employeeRole = Role::where('slug', Role::EMPLOYEE)->first();

        $itDept = Department::where('name', 'like', '%Tecnologías%')->first();
        $hrDept = Department::where('name', 'like', '%Recursos Humanos%')->first();

        $defaultPassword = Hash::make('password123');

        $users = [
            [
                'name' => 'Administrador Global',
                'email' => 'admin@helpdesk.local',
                'password' => $defaultPassword,
                'role_id' => $adminRole->id,
                'department_id' => $itDept?->id,
                'phone' => '+52 555 100 0001',
                'is_active' => true,
            ],
            [
                'name' => 'Carlos Supervisor',
                'email' => 'supervisor@helpdesk.local',
                'password' => $defaultPassword,
                'role_id' => $supervisorRole->id,
                'department_id' => $itDept?->id,
                'phone' => '+52 555 100 0002',
                'is_active' => true,
            ],
            [
                'name' => 'Mateo Técnico',
                'email' => 'tecnico@helpdesk.local',
                'password' => $defaultPassword,
                'role_id' => $technicianRole->id,
                'department_id' => $itDept?->id,
                'phone' => '+52 555 100 0003',
                'is_active' => true,
            ],
            [
                'name' => 'Lucía Empleada',
                'email' => 'empleado@helpdesk.local',
                'password' => $defaultPassword,
                'role_id' => $employeeRole->id,
                'department_id' => $hrDept?->id,
                'phone' => '+52 555 100 0004',
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(['email' => $user['email']], $user);
        }
    }
}
