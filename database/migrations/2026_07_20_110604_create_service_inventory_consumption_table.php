<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_inventory_consumption', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')
                ->constrained('services')
                ->restrictOnDelete();
            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->restrictOnDelete();
            $table->decimal('quantity_per_service', 10, 2);
            $table->timestamps();

            $table->unique(['service_id', 'inventory_item_id'], 'service_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_inventory_consumption');
    }
};
