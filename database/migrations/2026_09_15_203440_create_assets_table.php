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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_code', 50)->unique(); // e.g. LAP-00452
            $table->string('name', 150);                    // e.g. Dell Latitude 5420
            $table->string('type', 50);                     // Laptop, Desktop, Servidor, Impresora, Switch
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable()->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('operating_system', 100)->nullable();
            $table->string('location', 150)->nullable();    // Edificio B, Piso 2
            $table->string('status', 50)->default('active'); // active, maintenance, retired
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
