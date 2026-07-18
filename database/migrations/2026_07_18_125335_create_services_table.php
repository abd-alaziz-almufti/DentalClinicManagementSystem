<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();          // e.g. SRV001 — stable identifier, name may change
            $table->foreignId('service_category_id')
                ->constrained('service_categories')
                ->restrictOnDelete();                       // don't allow deleting a category that has services
            $table->string('name', 150);
            $table->decimal('default_price', 10, 2);
            $table->unsignedInteger('duration')->nullable(); // minutes
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();                           // safe: historical visit_services store a price snapshot,
                                                               // so deleting a service does not corrupt past invoices
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
