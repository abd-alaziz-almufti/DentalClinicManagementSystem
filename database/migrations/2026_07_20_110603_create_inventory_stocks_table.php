<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->restrictOnDelete();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->decimal('quantity_on_hand', 10, 2)->default(0.00);
            $table->decimal('reorder_level', 10, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['inventory_item_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
