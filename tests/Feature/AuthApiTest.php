<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(DepartmentSeeder::class);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $role = Role::where('slug', Role::EMPLOYEE)->first();
        $user = User::factory()->create([
            'email' => 'user@test.local',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@test.local',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role_id'],
                'token',
            ]);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $role = Role::where('slug', Role::EMPLOYEE)->first();
        User::factory()->create([
            'email' => 'user@test.local',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@test.local',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_new_user_can_register_via_api(): void
    {
        $department = Department::first();

        $response = $this->postJson('/api/register', [
            'name' => 'Nuevo Empleado',
            'email' => 'nuevo@test.local',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'department_id' => $department->id,
            'phone' => '+52 555 999 8888',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@test.local',
        ]);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $role = Role::where('slug', Role::ADMIN)->first();
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role.slug', Role::ADMIN);
    }

    public function test_admin_can_access_protected_resource(): void
    {
        $adminRole = Role::where('slug', Role::ADMIN)->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Assets list is accessible to all authenticated users, admins can also create
        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/assets');

        $response->assertStatus(200);
    }

    public function test_employee_cannot_access_admin_only_resource(): void
    {
        $employeeRole = Role::where('slug', Role::EMPLOYEE)->first();
        $employee = User::factory()->create(['role_id' => $employeeRole->id]);

        // Creating assets requires admin/supervisor role
        $response = $this->actingAs($employee, 'sanctum')->postJson('/api/assets', [
            'inventory_code' => 'INV-001',
            'name'           => 'Laptop Test',
            'type'           => 'laptop',
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Acceso denegado. No posee los permisos necesarios para realizar esta acción.']);
    }
}
