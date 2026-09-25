<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            [
                'name' => 'Crítica',
                'slug' => Priority::CRITICAL,
                'sla_hours' => 2,
                'color' => '#ef4444', // Red
                'description' => 'Afectación total de servicios críticos de la empresa. Respuesta y resolución inmediata.',
            ],
            [
                'name' => 'Alta',
                'slug' => Priority::HIGH,
                'sla_hours' => 8,
                'color' => '#f97316', // Orange
                'description' => 'Problemas graves que impiden el trabajo regular sin alternativa inmediata.',
            ],
            [
                'name' => 'Media',
                'slug' => Priority::MEDIUM,
                'sla_hours' => 24,
                'color' => '#eab308', // Yellow
                'description' => 'Incidencias que afectan parcialmente el trabajo o tienen soluciones temporales.',
            ],
            [
                'name' => 'Baja',
                'slug' => Priority::LOW,
                'sla_hours' => 48,
                'color' => '#10b981', // Green
                'description' => 'Consultas, mejoras, mantenimientos programados o peticiones sin urgencia.',
            ],
        ];

        foreach ($priorities as $priority) {
            Priority::firstOrCreate(['slug' => $priority['slug']], $priority);
        }
    }
}
