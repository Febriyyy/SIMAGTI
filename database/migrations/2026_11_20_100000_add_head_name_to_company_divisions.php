<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama ketua divisi tempat mahasiswa magang, untuk tanda tangan di sertifikat KP.
 * Logo perusahaan memakai kolom companies.logo_file_id yang sudah ada; logo kampus disimpan di tabel settings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_divisions', function (Blueprint $table) {
            $table->string('head_name', 150)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('company_divisions', fn (Blueprint $table) => $table->dropColumn('head_name'));
    }
};
