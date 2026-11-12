<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Portofolio KP publik pilihan mahasiswa: satu per KP. Tautan publik hanya aktif bila mahasiswa menyalakannya;
 * mahasiswa memilih hasil kerja (task selesai) yang ditampilkan dan apakah nilai akhir ikut tampil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token', 40)->unique();
            $table->boolean('is_public')->default(false);
            $table->boolean('show_grade')->default(false);
            $table->string('headline', 120)->nullable();
            $table->text('about')->nullable();
            $table->json('skills')->nullable();
            $table->json('task_ids')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};
