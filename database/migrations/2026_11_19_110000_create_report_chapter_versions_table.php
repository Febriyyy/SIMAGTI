<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat versi berkas bab laporan: setiap unggahan disimpan (berkas lama tidak lagi dihapus),
 * sehingga dosen pembimbing bisa membandingkan sebelum dan sesudah revisi.
 * Berkas bab yang sudah ada dicatat sebagai versi terakhirnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_chapter_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['report_chapter_id', 'version']);
        });

        DB::table('report_chapters')->whereNotNull('file_id')->orderBy('id')->each(function ($chapter) {
            DB::table('report_chapter_versions')->insert([
                'report_chapter_id' => $chapter->id,
                'file_id' => $chapter->file_id,
                'version' => max(1, (int) $chapter->version),
                'submitted_at' => $chapter->submitted_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_chapter_versions');
    }
};
