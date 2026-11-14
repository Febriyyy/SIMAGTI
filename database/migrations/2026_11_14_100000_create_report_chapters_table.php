<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan KP per bab. Bab yang memuat informasi perusahaan (needs_supervisor) disetujui Company Supervisor dulu,
 * baru diteruskan ke dosen pembimbing. Alur: belum -> menunggu_supervisor -> menunggu_dosen -> disetujui,
 * atau revisi (dengan catatan) lalu diunggah ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('title', 150);
            $table->boolean('needs_supervisor')->default(false);
            $table->string('status', 30)->default('belum')->index();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->unsignedSmallInteger('version')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('supervisor_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('revision_note')->nullable();
            $table->foreignId('revision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revision_at')->nullable();
            $table->timestamps();

            $table->unique(['internship_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_chapters');
    }
};
