<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->unique()                          // 1-to-1 with users
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('specialty_id')
                ->constrained('specialties')
                ->restrictOnDelete();
            $table->string('license_number', 50)->nullable();
            $table->string('color', 7)->nullable();  // hex color for calendar/schedule UI, e.g. #2563EB
            $table->string('signature', 255)->nullable(); // path to signature image, used on prescriptions/reports
            $table->text('bio')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
