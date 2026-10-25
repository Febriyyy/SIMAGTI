<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KP v2 tahap 2: konfirmasi supervisor, siklus bulanan (refleksi mahasiswa + penilaian singkat),
 * log bimbingan, dokumen (surat pengantar, surat balasan, pakta integritas), hasil akhir dan nilai MBKM,
 * rekognisi SKS, pengajuan perubahan, dan kuesioner/rubrik milik dosen pembimbing.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Penempatan ----------
        Schema::table('internships', function (Blueprint $table) {
            // Konfirmasi Company Supervisor atas data penempatan (posisi, divisi, periode)
            $table->string('supervisor_confirmation', 12)->nullable()->after('supervisor_phone'); // confirmed | disputed
            $table->text('supervisor_confirmation_note')->nullable()->after('supervisor_confirmation');
            $table->timestamp('supervisor_confirmed_at')->nullable()->after('supervisor_confirmation_note');

            // Kuesioner / rubrik penilaian akhir yang dipilih dosen pembimbing (kosong = rubrik aktif kampus)
            $table->foreignId('assessment_rubric_id')->nullable()->after('lecturer_id')->constrained('assessment_rubrics')->nullOnDelete();

            // Hasil akhir: KP Industri dan Seminar = diterima / tidak; MBKM = nilai angka dan huruf (60% mitra + 40% dosen)
            $table->string('result', 12)->nullable()->after('status');
            $table->text('result_note')->nullable()->after('result');
            $table->decimal('lecturer_score', 5, 2)->nullable()->after('result_note');
            $table->decimal('final_score', 5, 2)->nullable()->after('lecturer_score');
            $table->string('final_grade', 3)->nullable()->after('final_score');
            $table->foreignId('result_by')->nullable()->after('final_grade')->constrained('users')->nullOnDelete();
            $table->timestamp('result_at')->nullable()->after('result_by');
        });

        Schema::table('rubric_aspects', function (Blueprint $table) {
            // score = nilai 0-100 berbobot | text = pertanyaan isian (tidak memengaruhi nilai)
            $table->string('kind', 10)->default('score')->after('description');
        });

        Schema::table('assessment_rubrics', function (Blueprint $table) {
            // Kuesioner milik dosen pembimbing (kosong = rubrik kampus)
            $table->foreignId('owner_id')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });

        Schema::table('assessment_scores', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->change();
        });

        // ---------- Siklus bulanan ----------
        Schema::create('monthly_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('month_number'); // bulan KP ke-1, 2, ...
            $table->date('period_start');
            $table->date('period_end');

            // Refleksi mahasiswa
            $table->text('learned')->nullable();
            $table->text('obstacles')->nullable();
            $table->text('plan')->nullable();
            $table->json('syllabus_item_ids')->nullable(); // tujuan silabus yang terasa ada kemajuan
            $table->timestamp('reflected_at')->nullable();

            // Penilaian Company Supervisor (seminar: dosen pembimbing), skala 1-4
            $table->unsignedTinyInteger('score_hard')->nullable();
            $table->unsignedTinyInteger('score_soft')->nullable();
            $table->unsignedTinyInteger('score_attitude')->nullable();
            $table->text('reviewer_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            // Catatan dosen pembimbing
            $table->text('lecturer_note')->nullable();
            $table->foreignId('lecturer_noted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('lecturer_noted_at')->nullable();

            $table->timestamps();
            $table->unique(['internship_id', 'month_number']);
        });

        // ---------- Log bimbingan dengan dosen ----------
        Schema::create('guidance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->date('held_on');
            $table->string('mode', 10)->default('daring'); // daring | luring
            $table->string('topic', 200);
            $table->text('notes')->nullable();
            $table->string('status', 12)->default('pending')->index(); // pending | confirmed | rejected
            $table->text('lecturer_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['internship_id', 'held_on']);
        });

        // ---------- Dokumen KP ----------
        Schema::create('internship_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // surat_pengantar | surat_balasan | pakta_integritas
            // surat pengantar: diminta -> terbit; unggahan: menunggu -> diunggah -> diterima | ditolak
            $table->string('status', 12)->index();
            $table->string('number', 60)->nullable()->unique(); // nomor surat pengantar
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['internship_id', 'type']);
        });

        // ---------- Rekognisi SKS (MBKM) ----------
        Schema::create('credit_recognitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('courses'); // [{code, name, credits}]
            $table->string('status', 12)->default('diajukan')->index(); // diajukan | disetujui | ditolak
            $table->text('note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        // ---------- Pengajuan perubahan ----------
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // extend | transfer | withdraw | replace_supervisor
            $table->string('status', 12)->default('pending')->index(); // pending | approved | rejected
            $table->string('step', 12)->nullable(); // supervisor | kaprodi | tu: siapa yang memutuskan berikutnya
            $table->json('payload')->nullable();
            $table->text('reason');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('supervisor_note')->nullable();
            $table->timestamp('supervisor_acked_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['internship_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
        Schema::dropIfExists('credit_recognitions');
        Schema::dropIfExists('internship_documents');
        Schema::dropIfExists('guidance_sessions');
        Schema::dropIfExists('monthly_reviews');

        Schema::table('assessment_rubrics', fn (Blueprint $table) => $table->dropConstrainedForeignId('owner_id'));
        Schema::table('rubric_aspects', fn (Blueprint $table) => $table->dropColumn('kind'));

        Schema::table('internships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_rubric_id');
            $table->dropConstrainedForeignId('result_by');
            $table->dropColumn(['supervisor_confirmation', 'supervisor_confirmation_note', 'supervisor_confirmed_at',
                'result', 'result_note', 'lecturer_score', 'final_score', 'final_grade', 'result_at']);
        });
    }
};
