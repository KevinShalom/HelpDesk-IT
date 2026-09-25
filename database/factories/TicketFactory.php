<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => 'HD-' . $this->faker->unique()->numberBetween(100000, 999999),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'category_id' => Category::inRandomOrder()->first()->id ?? Category::factory(),
            'priority_id' => Priority::inRandomOrder()->first()->id ?? Priority::factory(),
            'department_id' => Department::inRandomOrder()->first()->id ?? Department::factory(),
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'status' => Ticket::STATUS_NEW,
            'sla_due_at' => now()->addHours(24),
        ];
    }
}
