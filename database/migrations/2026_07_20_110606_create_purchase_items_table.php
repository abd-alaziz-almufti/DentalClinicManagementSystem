<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->cascadeOnDelete();
            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->restrictOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->timestamps();

            $table->index('purchase_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
