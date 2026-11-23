<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tanda tangan ketua divisi (gambar) untuk sertifikat KP, diisi Company Supervisor bersama nama ketua divisi. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_divisions', function (Blueprint $table) {
            $table->foreignId('head_signature_file_id')->nullable()->after('head_name')->constrained('files')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_divisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_signature_file_id');
        });
    }
};
