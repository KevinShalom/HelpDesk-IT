<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hardware y Equipos',
                'slug' => 'hardware',
                'description' => 'Fallas en laptops, computadoras, monitores, periféricos o impresoras.',
            ],
            [
                'name' => 'Software y Aplicaciones',
                'slug' => 'software',
                'description' => 'Instalación, errores de software, licencias y suites ofimáticas.',
            ],
            [
                'name' => 'Redes y Conectividad',
                'slug' => 'networks',
                'description' => 'Problemas con Wi-Fi, cable de red, VPN o acceso a internet.',
            ],
            [
                'name' => 'Cuentas y Accesos',
                'slug' => 'access-accounts',
                'description' => 'Restablecimiento de contraseñas, permisos de carpetas y cuentas de correo.',
            ],
            [
                'name' => 'Telefonía y Comunicación',
                'slug' => 'telephony',
                'description' => 'Teléfonos IP, extensiones y salas de conferencias.',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
