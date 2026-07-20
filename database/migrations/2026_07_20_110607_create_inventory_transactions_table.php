<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->restrictOnDelete();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->enum('type', ['consumption', 'return', 'purchase_in', 'adjustment']);
            $table->decimal('quantity', 10, 2); // Positive or negative
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('item_name_snapshot', 150);
            $table->string('item_unit_snapshot', 30);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            // Indexes for fast auditing
            $table->index(['inventory_item_id', 'branch_id']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
