<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();       // e.g. GEN, ORTHO, ORAL_SURG, ENDO, PEDO
            $table->string('name', 100);                // e.g. General Dentist, Orthodontist
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specialties');
    }
};
