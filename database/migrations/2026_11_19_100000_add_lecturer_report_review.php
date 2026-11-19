<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peran dosen pembimbing pada laporan dan sidang KP:
 * - final_reports: laporan akhir utuh (satu per KP).
 * - report_comments: diskusi per bab atau laporan akhir; boleh berlampiran berkas koreksi.
 * - report_scores: nilai laporan akhir dari dosen pembimbing (4 aspek berbobot poin, total 0–100).
 * - defense_scores: komponen nilai sidang (dosen 50% + industri 50%) dan pengesahan kaprodi.
 * - guidance_sessions: jadwal bimbingan dari dosen dengan jam dan tautan rapat daring.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->unsignedInteger('version')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('report_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            // null = diskusi laporan akhir
            $table->foreignId('report_chapter_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->timestamps();
            $table->index(['internship_id', 'report_chapter_id']);
        });

        Schema::create('report_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('writing');      // Tata penulisan laporan, maks 35
            $table->unsignedTinyInteger('analysis');     // Pengamatan dan pembahasan, maks 25
            $table->unsignedTinyInteger('explaining');   // Menjelaskan laporan, maks 25
            $table->unsignedTinyInteger('conduct');      // Penampilan dan sikap, maks 15
            $table->unsignedTinyInteger('total');
            $table->text('note')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
        });

        Schema::table('defense_scores', function (Blueprint $table) {
            $table->decimal('lecturer_score', 5, 2)->nullable()->after('score');
            $table->decimal('industry_score', 5, 2)->nullable()->after('lecturer_score');
        });

        Schema::table('guidance_sessions', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('held_on');
            $table->string('meeting_link', 300)->nullable()->after('mode');
        });
    }

    public function down(): void
    {
        Schema::table('guidance_sessions', fn (Blueprint $table) => $table->dropColumn(['start_time', 'meeting_link']));
        Schema::table('defense_scores', fn (Blueprint $table) => $table->dropColumn(['lecturer_score', 'industry_score']));
        Schema::dropIfExists('report_scores');
        Schema::dropIfExists('report_comments');
        Schema::dropIfExists('final_reports');
    }
};
