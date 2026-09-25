<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 30)->unique(); // e.g. HD-000101
            $table->string('title', 255);
            $table->text('description');

            // Categorization & Priority
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('priority_id')->constrained('priorities')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();

            // Participants
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Requester / Empleado
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Técnico asignado

            // Status Lifecycle: new, assigned, in_progress, waiting_user, resolved, closed, escalated
            $table->string('status', 30)->default('new');

            // SLA Tracking
            $table->timestamp('sla_due_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Resolution data
            $table->text('diagnostic')->nullable();
            $table->text('solution')->nullable();
            $table->unsignedInteger('work_time_minutes')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
