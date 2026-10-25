<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        // Satu baris = satu penempatan magang. Pusat dari hampir semua fitur.
        // Mentor dan ketua divisi disimpan di tabel internship_staff.
        Schema::create('internships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('company_divisions')->nullOnDelete();
            $table->foreignId('lecturer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('academic_period_id')->nullable()->constrained()->nullOnDelete();
            // lowongan | mandiri
            $table->string('track', 20)->default('lowongan');
            // diajukan | berjalan | selesai | ditolak
            $table->string('status', 20)->default('diajukan')->index();
            // Terisi (= student_id) hanya saat status diajukan/berjalan, dijaga oleh model.
            // Kolom unik ini memastikan satu mahasiswa hanya punya satu penempatan aktif.
            $table->unsignedBigInteger('active_student_id')->nullable()->unique();
            $table->date('start_date');
            $table->date('end_date');
            // Terisi saat KP selesai: setelah itu penempatan hanya bisa dibaca.
            $table->timestamp('locked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internships');
    }
};
