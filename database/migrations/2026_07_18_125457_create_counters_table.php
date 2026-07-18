<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();
            $table->string('type', 30);            // patient | invoice | visit | appointment ...
            $table->unsignedSmallInteger('year');   // sequence resets per calendar year
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            // one counter row per branch + document type + year
            $table->unique(['branch_id', 'type', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
