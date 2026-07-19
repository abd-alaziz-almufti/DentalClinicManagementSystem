<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teeth', function (Blueprint $table) {
            $table->id();
            $table->string('fdi_number', 3)->unique(); // e.g. "11", "26", "48"
            $table->string('name', 100);                // e.g. "Upper Right Central Incisor"
            $table->unsignedTinyInteger('quadrant');     // 1-4 (FDI quadrants)
            $table->unsignedTinyInteger('position_in_quadrant'); // 1-8
            $table->enum('type', ['permanent', 'primary'])->default('permanent');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            // Pure reference/lookup data (same treatment as specialties,
            // service_categories) — no softDeletes needed.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teeth');
    }
};
