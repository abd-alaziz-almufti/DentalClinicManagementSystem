<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tooth_surfaces', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // M, D, O, B, L, I
            $table->string('name', 50);            // Mesial, Distal, Occlusal, Buccal, Lingual, Incisal
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tooth_surfaces');
    }
};
