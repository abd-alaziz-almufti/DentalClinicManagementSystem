<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();   // e.g. MAIN, NBL, RAM — used in document numbering
            $table->string('name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('logo', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
