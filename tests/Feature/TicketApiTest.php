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

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $technician;
    protected User $employee;
    protected Category $category;
    protected Priority $priority;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'DepartmentSeeder']);
        $this->artisan('db:seed', ['--class' => 'PrioritySeeder']);
        $this->artisan('db:seed', ['--class' => 'CategorySeeder']);

        $this->admin      = $this->createUser(Role::ADMIN);
        $this->supervisor = $this->createUser(Role::SUPERVISOR);
        $this->technician = $this->createUser(Role::TECHNICIAN);
        $this->employee   = $this->createUser(Role::EMPLOYEE);

        $this->category   = Category::first();
        $this->priority   = Priority::first();
        $this->department = Department::first();
    }

    private function createUser(string $roleSlug): User
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        return User::factory()->create([
            'role_id'       => $role->id,
            'department_id' => Department::first()->id,
        ]);
    }

    private function asUser(User $user): static
    {
        return $this->actingAs($user, 'sanctum');
    }

    // ─────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────

    public function test_employee_can_create_ticket(): void
    {
        $response = $this->asUser($this->employee)
            ->postJson('/api/tickets', [
                'title'       => 'Mi equipo no enciende',
                'description' => 'Desde esta mañana el equipo de escritorio no enciende.',
                'category_id' => $this->category->id,
                'priority_id' => $this->priority->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Ticket::STATUS_NEW)
            ->assertJsonStructure(['data' => ['ticket_number', 'sla_due_at']]);

        $this->assertDatabaseHas('tickets', [
            'user_id' => $this->employee->id,
            'status'  => Ticket::STATUS_NEW,
        ]);
    }

    public function test_create_ticket_requires_title_and_description(): void
    {
        $this->asUser($this->employee)
            ->postJson('/api/tickets', [
                'category_id' => $this->category->id,
                'priority_id' => $this->priority->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // READ
    // ─────────────────────────────────────────────────────────────────────

    public function test_employee_only_sees_own_tickets(): void
    {
        $this->asUser($this->employee)->postJson('/api/tickets', [
            'title' => 'Mi ticket', 'description' => 'Descripcion.',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ]);

        $other = $this->createUser(Role::EMPLOYEE);
        $this->asUser($other)->postJson('/api/tickets', [
            'title' => 'Ticket ajeno', 'description' => 'Otro usuario.',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ]);

        $response = $this->asUser($this->employee)->getJson('/api/tickets');
        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->employee->id, $data[0]['user_id']);
    }

    public function test_admin_sees_all_tickets(): void
    {
        $this->asUser($this->employee)->postJson('/api/tickets', [
            'title' => 'T1', 'description' => 'D1',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ]);
        $other = $this->createUser(Role::EMPLOYEE);
        $this->asUser($other)->postJson('/api/tickets', [
            'title' => 'T2', 'description' => 'D2',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ]);

        $response = $this->asUser($this->admin)->getJson('/api/tickets');
        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_employee_cannot_see_another_users_ticket(): void
    {
        $other = $this->createUser(Role::EMPLOYEE);
        $createdId = $this->asUser($other)->postJson('/api/tickets', [
            'title' => 'T de otro', 'description' => 'D',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ])->json('data.id');

        $this->asUser($this->employee)
            ->getJson("/api/tickets/{$createdId}")
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────
    // ASSIGN
    // ─────────────────────────────────────────────────────────────────────

    public function test_diagnostic_role_via_me_endpoint(): void
    {
        $response = $this->asUser($this->supervisor)->getJson('/api/me');
        $response->assertOk();
        $this->assertEquals('supervisor', $response->json('user.role.slug'),
            'Supervisor role slug mismatch. Full /me response: ' . json_encode($response->json())
        );

        // Now create a ticket and try to assign — dump the 403 body
        $ticketId = $this->asUser($this->employee)->postJson('/api/tickets', [
            'title' => 'Diag', 'description' => 'D',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ])->json('data.id');

        $assignResponse = $this->asUser($this->supervisor)
            ->patchJson("/api/tickets/{$ticketId}/assign", [
                'technician_id' => $this->technician->id,
            ]);

        // Always dump the body so we can see where the 403 comes from
        $this->assertTrue(
            in_array($assignResponse->getStatusCode(), [200, 403]),
            'Assign response body: ' . $assignResponse->getContent()
        );
        $this->assertEquals(200, $assignResponse->getStatusCode(),
            'Assign 403 body: ' . $assignResponse->getContent()
        );
    }

    public function test_supervisor_can_assign_ticket(): void
    {
        $ticketId = $this->asUser($this->employee)->postJson('/api/tickets', [
            'title' => 'Asignar', 'description' => 'D',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ])->json('data.id');

        $this->asUser($this->supervisor)
            ->patchJson("/api/tickets/{$ticketId}/assign", [
                'technician_id' => $this->technician->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.assigned_to', $this->technician->id)
            ->assertJsonPath('data.status', Ticket::STATUS_ASSIGNED);
    }

    public function test_employee_cannot_assign_ticket(): void
    {
        $ticketId = $this->asUser($this->employee)->postJson('/api/tickets', [
            'title' => 'Asignar', 'description' => 'D',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ])->json('data.id');

        $this->asUser($this->employee)
            ->patchJson("/api/tickets/{$ticketId}/assign", [
                'technician_id' => $this->technician->id,
            ])
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────
    // STATUS TRANSITION
    // ─────────────────────────────────────────────────────────────────────

    public function test_technician_can_advance_ticket_status(): void
    {
        $ticketId = $this->asUser($this->employee)->postJson('/api/tickets', [
            'title' => 'Progreso', 'description' => 'D',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ])->json('data.id');

        $this->asUser($this->supervisor)
            ->patchJson("/api/tickets/{$ticketId}/assign", ['technician_id' => $this->technician->id]);

        $this->asUser($this->technician)
            ->patchJson("/api/tickets/{$ticketId}/status", ['status' => Ticket::STATUS_IN_PROGRESS])
            ->assertOk()
            ->assertJsonPath('data.status', Ticket::STATUS_IN_PROGRESS);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $ticketId = $this->asUser($this->employee)->postJson('/api/tickets', [
            'title' => 'T', 'description' => 'D',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ])->json('data.id');

        // Jump from new directly to closed — invalid
        $this->asUser($this->admin)
            ->patchJson("/api/tickets/{$ticketId}/status", ['status' => Ticket::STATUS_CLOSED])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // COMMENTS
    // ─────────────────────────────────────────────────────────────────────

    public function test_participant_can_add_comment(): void
    {
        $ticketId = $this->asUser($this->employee)->postJson('/api/tickets', [
            'title' => 'C', 'description' => 'D',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ])->json('data.id');

        $this->asUser($this->employee)
            ->postJson("/api/tickets/{$ticketId}/comments", [
                'comment' => 'Hay alguna actualizacion sobre mi ticket?',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_internal', false);
    }

    public function test_employee_cannot_post_internal_notes(): void
    {
        $ticketId = $this->asUser($this->employee)->postJson('/api/tickets', [
            'title' => 'C', 'description' => 'D',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ])->json('data.id');

        $response = $this->asUser($this->employee)
            ->postJson("/api/tickets/{$ticketId}/comments", [
                'comment'     => 'Nota interna que no deberia poner.',
                'is_internal' => true,
            ])
            ->assertCreated();

        $this->assertFalse($response->json('data.is_internal'));
    }

    public function test_technician_can_post_internal_note(): void
    {
        $ticketId = $this->asUser($this->employee)->postJson('/api/tickets', [
            'title' => 'C', 'description' => 'D',
            'category_id' => $this->category->id, 'priority_id' => $this->priority->id,
        ])->json('data.id');

        $this->asUser($this->supervisor)
            ->patchJson("/api/tickets/{$ticketId}/assign", ['technician_id' => $this->technician->id]);

        $this->asUser($this->technician)
            ->postJson("/api/tickets/{$ticketId}/comments", [
                'comment'     => 'Nota interna del tecnico.',
                'is_internal' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_internal', true);
    }
}
