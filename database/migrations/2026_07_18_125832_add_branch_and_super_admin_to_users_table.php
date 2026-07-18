<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // nullable: a Super Admin may not belong to a single branch
            $table->foreignId('branch_id')
                ->nullable()
                ->after('id')
                ->constrained('branches')
                ->nullOnDelete();

            // NOTE: this is a denormalized *performance flag*, not the source of truth.
            // The real source of truth is the Spatie "super-admin" role.
            // Keep this column in sync via a Model Observer whenever the role is
            // assigned/removed, so queries can check it without an extra join.
            $table->boolean('is_super_admin')->default(false)->after('branch_id');

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('is_super_admin');
            $table->dropSoftDeletes();
        });
    }
};
