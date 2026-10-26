<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SIMAGTI v3 (revisi dosen):
 * - Peran Biro Kerjasama dihapus (tugasnya ke TU Fakultas), peran Admin ditambahkan.
 * - Satu jenis KP saja: tidak ada lagi jalur seminar maupun MBKM (rekognisi SKS, pakta integritas, 60/40).
 * - Silabus dan evaluasi bulanan dihapus. Penilaian akhir supervisor memakai standar kampus:
 *   pertanyaan per kategori (hard skill, soft skill, attitude) yang dijawab dengan skala nilai.
 * - Timesheet menjadi rencana kegiatan (Gantt), task dibuat di bawah rencana; goals di halaman Kegiatan.
 * - Durasi jam dihapus dari catatan kegiatan; kolom Review dihapus dari board.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'biro')->update(['role' => 'admin']);
        DB::table('internship_approvals')->where('step', 'biro_verification')->update(['step' => 'tu_verification']);

        // Goals (tujuan KP mahasiswa) dan rencana kegiatan (timesheet / Gantt)
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->timestamp('achieved_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['internship_id', 'position']);
        });

        Schema::create('activity_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['internship_id', 'start_date']);
        });

        // Kolom Review dihapus: task di dalamnya pindah ke kolom "sedang dikerjakan" pertama
        foreach (DB::table('board_columns')->where('name', 'Review')->get() as $review) {
            $target = DB::table('board_columns')->where('internship_id', $review->internship_id)
                ->where('id', '!=', $review->id)->where('category', 'in_progress')->orderBy('position')->value('id');
            if ($target) {
                DB::table('weekly_tasks')->where('board_column_id', $review->id)->update(['board_column_id' => $target]);
                DB::table('board_columns')->where('id', $review->id)->delete();
            }
        }

        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('syllabus_item_id');
        });
        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->dropColumn('estimate_hours');
            $table->foreignId('activity_plan_id')->nullable()->after('board_column_id')->constrained()->nullOnDelete();
            $table->foreignId('goal_id')->nullable()->after('activity_plan_id')->constrained()->nullOnDelete();
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn('hours');
        });

        Schema::dropIfExists('syllabus_items');
        Schema::dropIfExists('monthly_reviews');
        Schema::dropIfExists('credit_recognitions');
        DB::table('internship_documents')->where('type', 'pakta_integritas')->delete();

        Schema::table('internships', function (Blueprint $table) {
            $table->dropIndex(['program', 'status']);
            $table->dropConstrainedForeignId('syllabus_reviewed_by');
            $table->dropConstrainedForeignId('assessment_rubric_id');
        });
        Schema::table('internships', function (Blueprint $table) {
            $table->dropColumn(['program', 'program_type', 'topic', 'organizer', 'lecturer_score',
                'syllabus_status', 'syllabus_note', 'syllabus_reviewed_at']);
        });

        // Standar penilaian kampus: pertanyaan dikelompokkan per kategori; kuesioner milik dosen dihapus
        DB::table('assessment_rubrics')->whereNotNull('owner_id')->whereNotIn('id', DB::table('assessments')->select('rubric_id'))->delete();
        Schema::table('assessment_rubrics', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
        });
        Schema::table('rubric_aspects', function (Blueprint $table) {
            $table->string('category', 20)->default('hard')->after('rubric_id');
        });
        Schema::table('assessments', function (Blueprint $table) {
            // Bobot kategori yang dipakai saat nilai dihitung (agar nilai lama tidak berubah bila kaprodi mengubah bobot)
            $table->json('category_weights')->nullable()->after('grade_scale_version');
            $table->text('comment')->nullable()->after('category_weights'); // catatan umum Company Supervisor
        });
    }

    public function down(): void
    {
        Schema::table('assessments', fn (Blueprint $table) => $table->dropColumn(['category_weights', 'comment']));
        Schema::table('rubric_aspects', fn (Blueprint $table) => $table->dropColumn('category'));
        Schema::table('assessment_rubrics', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->string('program', 20)->default('magang')->after('track');
            $table->string('program_type', 20)->default('industri')->after('program');
            $table->string('topic', 200)->nullable();
            $table->string('organizer', 150)->nullable();
            $table->decimal('lecturer_score', 5, 2)->nullable();
            $table->string('syllabus_status', 20)->default('draft');
            $table->text('syllabus_note')->nullable();
            $table->foreignId('syllabus_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('syllabus_reviewed_at')->nullable();
            $table->foreignId('assessment_rubric_id')->nullable()->constrained('assessment_rubrics')->nullOnDelete();
            $table->index(['program', 'status']);
        });

        Schema::create('syllabus_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->string('category', 20);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
        Schema::create('monthly_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month_number');
            $table->timestamps();
        });
        Schema::create('credit_recognitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->json('courses');
            $table->string('status', 12)->default('diajukan');
            $table->timestamps();
        });

        Schema::table('time_entries', fn (Blueprint $table) => $table->decimal('hours', 4, 2)->default(0));

        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('goal_id');
            $table->dropConstrainedForeignId('activity_plan_id');
        });
        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->decimal('estimate_hours', 5, 2)->nullable();
            $table->foreignId('syllabus_item_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::dropIfExists('activity_plans');
        Schema::dropIfExists('goals');

        DB::table('internship_approvals')->where('step', 'tu_verification')->update(['step' => 'biro_verification']);
        DB::table('users')->where('role', 'admin')->update(['role' => 'biro']);
    }
};
