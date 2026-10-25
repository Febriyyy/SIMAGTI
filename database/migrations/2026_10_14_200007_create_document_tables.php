<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Folder dan berkas template dari kaprodi (surat, laporan, dokumen pendukung).
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('document_folders')->cascadeOnDelete();
            $table->string('name', 100);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_id', 'name']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // template_surat | template_laporan | pendukung
            $table->string('type', 30)->default('pendukung')->index();
            $table->boolean('is_published')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // Permintaan surat pengantar KP oleh mahasiswa.
        Schema::create('letter_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            // untuk perusahaan yang belum ada di sistem (KP mandiri)
            $table->string('company_name')->nullable();
            $table->text('purpose')->nullable();
            // diajukan | diproses | selesai | ditolak
            $table->string('status', 20)->default('diajukan')->index();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('result_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_requests');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_folders');
    }
};
