<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();

            // Nullable: some attachments belong to the patient generally
            // (e.g. an ID scan at registration), not to one specific visit.
            $table->foreignId('visit_id')
                ->nullable()
                ->constrained('visits')
                ->nullOnDelete();

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('type', ['x_ray', 'photo', 'document', 'consent_form']);

            // The Laravel Filesystem disk this file was stored on AT UPLOAD
            // TIME (e.g. 'public', 's3'). Stored explicitly per-row (not
            // read from config) so that switching FILESYSTEM_DISK later
            // doesn't break resolution of files uploaded under the old disk.
            $table->string('disk', 30);
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes

            $table->text('notes')->nullable();

            $table->timestamps();
            // softDeletes here IS appropriate (unlike pure lookup tables):
            // attachments are user-uploaded documents that may need
            // recovering (accidental delete) or have retention requirements
            // — unlike financial ledgers, "undo" is a reasonable operation.
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('visit_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
