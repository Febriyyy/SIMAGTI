<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tawaran kerja yang profesional: detail pekerjaan, surat resmi perusahaan (PDF), batas waktu jawaban,
 * serta alasan bila mahasiswa menolak. Status baru "kedaluwarsa" bila batas waktu lewat tanpa jawaban.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversion_candidates', function (Blueprint $table) {
            $table->string('unit', 100)->nullable()->after('offered_position');
            $table->string('work_location', 150)->nullable()->after('unit');
            $table->string('work_mode', 20)->nullable()->after('work_location');        // wfo | hybrid | remote
            $table->string('employment_type', 30)->nullable()->after('work_mode');      // tetap | kontrak | magang
            $table->unsignedTinyInteger('contract_months')->nullable()->after('employment_type');
            $table->date('start_date')->nullable()->after('contract_months');
            $table->text('job_description')->nullable()->after('start_date');
            $table->string('compensation', 150)->nullable()->after('job_description');
            $table->string('hr_contact_name', 100)->nullable()->after('compensation');
            $table->string('hr_contact_email', 150)->nullable()->after('hr_contact_name');
            $table->foreignId('offer_letter_id')->nullable()->after('hr_contact_email')->constrained('files')->nullOnDelete();
            $table->timestamp('offered_at')->nullable()->after('offer_letter_id');
            $table->date('respond_by')->nullable()->after('offered_at');
            $table->timestamp('responded_at')->nullable()->after('respond_by');
            $table->string('decline_reason', 30)->nullable()->after('responded_at');
            $table->text('decline_note')->nullable()->after('decline_reason');
        });
    }

    public function down(): void
    {
        Schema::table('conversion_candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offer_letter_id');
            $table->dropColumn(['unit', 'work_location', 'work_mode', 'employment_type', 'contract_months', 'start_date', 'job_description',
                'compensation', 'hr_contact_name', 'hr_contact_email', 'offered_at', 'respond_by', 'responded_at', 'decline_reason', 'decline_note']);
        });
    }
};
