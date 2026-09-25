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
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();       // Crítica, Alta, Media, Baja
            $table->string('slug', 50)->unique();       // critical, high, medium, low
            $table->unsignedInteger('sla_hours');       // 2, 8, 24, 48
            $table->string('color', 20)->default('#6b7280'); // Hex code for badges
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};
