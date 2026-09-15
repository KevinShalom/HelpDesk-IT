<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Tecnologías de la Información (IT)',
                'description' => 'Departamento encargado de infraestructura, redes y soporte técnico.',
            ],
            [
                'name' => 'Recursos Humanos',
                'description' => 'Gestión del personal y talento humano.',
            ],
            [
                'name' => 'Finanzas y Contabilidad',
                'description' => 'Administración financiera, compras y presupuestos.',
            ],
            [
                'name' => 'Operaciones y Logística',
                'description' => 'Operaciones generales, almacén y despacho.',
            ],
            [
                'name' => 'Dirección General',
                'description' => 'Gerencia y toma de decisiones estratégicas.',
            ],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept['name']], $dept);
        }
    }
}
