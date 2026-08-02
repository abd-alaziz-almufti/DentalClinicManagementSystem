<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Print-only — never enters any financial calculation
            // (007-settings/spec.md Clarification C2).
            $table->string('tax_number', 50)->nullable()->after('logo');

            // Cosmetic display string only — no multi-currency engine
            // (Clarification C3).
            $table->string('currency_code', 10)->default('SAR')->after('tax_number');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['tax_number', 'currency_code']);
        });
    }
};
