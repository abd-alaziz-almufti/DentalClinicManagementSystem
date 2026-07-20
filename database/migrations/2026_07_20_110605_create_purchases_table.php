<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number', 40)->unique();
            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();
            $table->decimal('total_cost', 12, 2);
            $table->enum('status', ['draft', 'received', 'cancelled'])->default('draft');
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('supplier_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
