<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $technician;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'DepartmentSeeder']);
        $this->artisan('db:seed', ['--class' => 'PrioritySeeder']);
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);

        $this->admin = $this->createUser(Role::ADMIN);
        $this->technician = $this->createUser(Role::TECHNICIAN);
        $this->employee = $this->createUser(Role::EMPLOYEE);
    }

    private function createUser(string $roleSlug): User
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        return User::factory()->create([
            'role_id' => $role->id,
            'department_id' => Department::first()->id,
        ]);
    }

    private function asUser(User $user): static
    {
        return $this->actingAs($user, 'sanctum');
    }

    public function test_dashboard_stats_structure_and_admin_view(): void
    {
        Ticket::factory()->create([
            'user_id' => $this->employee->id,
            'status' => Ticket::STATUS_NEW,
        ]);

        $response = $this->asUser($this->admin)->getJson('/api/dashboard/stats');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'overview' => [
                        'total', 'new', 'in_progress', 'resolved', 'closed'
                    ],
                    'sla' => [
                        'breached', 'warning'
                    ],
                    'by_priority'
                ]
            ]);

        $this->assertEquals(1, $response->json('data.overview.total'));
    }

    public function test_dashboard_stats_scoped_by_employee(): void
    {
        // Ticket for this employee
        Ticket::factory()->create([
            'user_id' => $this->employee->id,
            'status' => Ticket::STATUS_NEW,
        ]);

        // Ticket for another employee
        Ticket::factory()->create([
            'user_id' => $this->admin->id, // Just some other user
            'status' => Ticket::STATUS_NEW,
        ]);

        $response = $this->asUser($this->employee)->getJson('/api/dashboard/stats');

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.overview.total'));
    }
}
