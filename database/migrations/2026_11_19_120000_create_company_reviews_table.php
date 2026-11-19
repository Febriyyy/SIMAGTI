<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ulasan tempat magang dari mahasiswa setelah magang berakhir (satu per KP).
 * Tiga aspek bernilai 1–5, rekomendasi, dan komentar. `rating` = rata-rata tiga aspek
 * (disimpan agar rata-rata per perusahaan mudah dihitung untuk halaman Lowongan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('mentoring');   // bimbingan dan dukungan supervisor
            $table->unsignedTinyInteger('work_fit');    // kesesuaian tugas dengan bidang studi
            $table->unsignedTinyInteger('environment'); // lingkungan dan fasilitas kerja
            $table->decimal('rating', 3, 2);
            $table->boolean('recommend');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_reviews');
    }
};
