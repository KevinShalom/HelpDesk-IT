<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employee = User::where('email', 'empleado@helpdesk.local')->first();
        $hrDept = Department::where('name', 'like', '%Recursos Humanos%')->first();
        $itDept = Department::where('name', 'like', '%Tecnologías%')->first();

        $assets = [
            [
                'inventory_code' => 'LAP-00452',
                'name' => 'Dell Latitude 5420',
                'type' => 'Laptop',
                'brand' => 'Dell',
                'model' => 'Latitude 5420',
                'serial_number' => 'DL5420-998811',
                'ip_address' => '192.168.10.45',
                'operating_system' => 'Windows 11 Pro',
                'location' => 'Edificio B - Piso 2',
                'status' => 'active',
                'user_id' => $employee?->id,
                'department_id' => $hrDept?->id,
            ],
            [
                'inventory_code' => 'SRV-00101',
                'name' => 'Servidor Principal BD',
                'type' => 'Servidor',
                'brand' => 'HP Enterprise',
                'model' => 'ProLiant DL380 Gen10',
                'serial_number' => 'HPE-DL380-0011',
                'ip_address' => '192.168.1.10',
                'operating_system' => 'Ubuntu Server 22.04 LTS',
                'location' => 'Data Center - Rack 1',
                'status' => 'active',
                'user_id' => null,
                'department_id' => $itDept?->id,
            ],
            [
                'inventory_code' => 'NET-00204',
                'name' => 'Switch Core 48 Puertos',
                'type' => 'Switch',
                'brand' => 'Cisco',
                'model' => 'Catalyst 9300',
                'serial_number' => 'CSCO-9300-48P',
                'ip_address' => '192.168.1.2',
                'operating_system' => 'Cisco IOS-XE',
                'location' => 'Data Center - Rack 2',
                'status' => 'active',
                'user_id' => null,
                'department_id' => $itDept?->id,
            ],
        ];

        foreach ($assets as $asset) {
            Asset::firstOrCreate(['inventory_code' => $asset['inventory_code']], $asset);
        }
    }
}
