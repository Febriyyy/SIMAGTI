<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Template sertifikat kampus per program studi: tiap kaprodi mengisi nama dan tanda tangannya sendiri.
 * study_program_id kosong = template umum (dipakai prodi yang belum punya template sendiri).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->foreignId('study_program_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('study_program_id');
        });
    }
};
