<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Materi dari mentor untuk satu task (PDF, PPT, gambar). Berkasnya disimpan di tabel files.
        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_task_id')->constrained()->cascadeOnDelete();
            // satu berkas hanya milik satu lampiran
            $table->foreignId('file_id')->unique()->constrained('files')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('weekly_task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attachments');
    }
};
