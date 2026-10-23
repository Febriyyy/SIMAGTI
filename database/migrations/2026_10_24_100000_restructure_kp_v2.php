<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alur KP versi 2 (rapat 20 Okt 2026):
 * - Perusahaan hanya punya satu peran: Company Supervisor (gabungan mentor + ketua divisi). Ada peran TU Fakultas.
 * - Dua jalur KP: Magang (Industri / MBKM Mandiri) dan Seminar (Pelaksana / Peserta, tanpa perusahaan).
 * - Mahasiswa yang menyusun kegiatannya sendiri: silabus, proyek, board ala Jira/ClickUp, dan catatan waktu.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------- Peran ----------
        DB::table('users')->whereIn('role', ['mentor', 'ketua_divisi'])->update(['role' => 'supervisor']);
        DB::table('invitations')->whereIn('role', ['mentor', 'ketua_divisi'])->update(['role' => 'supervisor']);

        // Satu orang yang dulu mentor sekaligus ketua divisi di penempatan yang sama cukup tercatat sekali.
        $duplicates = DB::table('internship_staff')
            ->select('internship_id', 'user_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('internship_id', 'user_id')
            ->get();
        foreach ($duplicates as $row) {
            DB::table('internship_staff')
                ->where('internship_id', $row->internship_id)
                ->where('user_id', $row->user_id)
                ->where('id', '!=', $row->keep_id)
                ->delete();
        }
        DB::table('internship_staff')->update(['role' => 'supervisor']);

        // ---------- Penempatan ----------
        Schema::table('internships', function (Blueprint $table) {
            // magang | seminar
            $table->string('program', 20)->default('magang')->after('track');
            // magang: industri | mbkm ; seminar: pelaksana | peserta
            $table->string('program_type', 20)->default('industri')->after('program');
            // Posisi magang, diisi mahasiswa dan bisa dikoreksi Company Supervisor
            $table->string('position', 100)->nullable()->after('division_id');
            // Seminar: judul/topik kegiatan dan penyelenggaranya
            $table->string('topic', 200)->nullable()->after('position');
            $table->string('organizer', 150)->nullable()->after('topic');
            // Data Company Supervisor yang diisi mahasiswa; dipakai TU Fakultas untuk aktivasi akun
            $table->string('supervisor_name', 150)->nullable()->after('organizer');
            $table->string('supervisor_title', 100)->nullable()->after('supervisor_name');
            $table->string('supervisor_email')->nullable()->index()->after('supervisor_title');
            $table->string('supervisor_phone', 30)->nullable()->after('supervisor_email');
            // Ritme kerja mahasiswa: daily | weekly | monthly (satu periode = satu sprint)
            $table->string('cadence', 10)->default('weekly')->after('supervisor_phone');
            // Silabus: draft | submitted | approved | revision
            $table->string('syllabus_status', 20)->default('draft')->after('cadence');
            $table->text('syllabus_note')->nullable()->after('syllabus_status');
            $table->foreignId('syllabus_reviewed_by')->nullable()->after('syllabus_note')->constrained('users')->nullOnDelete();
            $table->timestamp('syllabus_reviewed_at')->nullable()->after('syllabus_reviewed_by');
            $table->index(['program', 'status']);
        });

        // Seminar tidak punya perusahaan.
        Schema::table('internships', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });

        // ---------- Silabus ----------
        Schema::create('syllabus_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            // hard | soft | attitude
            $table->string('category', 20);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['internship_id', 'category']);
        });

        // ---------- Proyek dan board ----------
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            // Awalan kode task, misalnya PMS -> PMS-12
            $table->string('key', 6);
            $table->text('description')->nullable();
            // nama warna dari palet aplikasi: green | blue | amber | pink | purple | gray
            $table->string('color', 20)->default('green');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['internship_id', 'key']);
        });

        Schema::create('board_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            // Arti kolom bagi sistem: todo | in_progress | done
            $table->string('category', 20)->default('todo');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['internship_id', 'position']);
        });

        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('internship_id')->constrained()->nullOnDelete();
            $table->foreignId('board_column_id')->nullable()->after('project_id')->constrained()->nullOnDelete();
            $table->foreignId('syllabus_item_id')->nullable()->after('board_column_id')->constrained()->nullOnDelete();
            // Nomor urut per penempatan, tampil sebagai KEY-12
            $table->unsignedInteger('number')->nullable()->after('syllabus_item_id');
            // low | medium | high | urgent
            $table->string('priority', 10)->default('medium')->after('status');
            $table->date('start_date')->nullable()->after('priority');
            $table->date('due_date')->nullable()->after('start_date');
            $table->decimal('estimate_hours', 5, 2)->nullable()->after('due_date');
            $table->json('labels')->nullable()->after('estimate_hours');
            $table->unsignedInteger('position')->default(0)->after('labels');

            $table->index(['internship_id', 'board_column_id', 'position']);
            $table->index(['internship_id', 'due_date']);
        });

        // Task dibuat mahasiswa: tidak lagi wajib terikat hari Senin-Jumat dan minggu ke-.
        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->unsignedSmallInteger('week_number')->nullable()->change();
            $table->string('day', 10)->nullable()->change();
        });

        // Task lama diberi nomor urut per penempatan, lalu nomor dijaga unik.
        $counter = [];
        foreach (DB::table('weekly_tasks')->orderBy('internship_id')->orderBy('id')->get(['id', 'internship_id']) as $row) {
            $counter[$row->internship_id] = ($counter[$row->internship_id] ?? 0) + 1;
            DB::table('weekly_tasks')->where('id', $row->id)->update(['number' => $counter[$row->internship_id]]);
        }
        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->unique(['internship_id', 'number']);
        });

        Schema::create('task_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weekly_task_id')->constrained()->cascadeOnDelete();
            $table->string('text', 200);
            $table->boolean('is_done')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        // ---------- Catatan waktu (timesheet / logbook) ----------
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weekly_task_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->decimal('hours', 4, 2);
            $table->text('description');
            // Foto dokumentasi (logbook MBKM)
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['internship_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('task_checklist_items');

        Schema::table('weekly_tasks', function (Blueprint $table) {
            $table->dropUnique(['internship_id', 'number']);
            $table->dropIndex(['internship_id', 'board_column_id', 'position']);
            $table->dropIndex(['internship_id', 'due_date']);
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('board_column_id');
            $table->dropConstrainedForeignId('syllabus_item_id');
            $table->dropColumn(['number', 'priority', 'start_date', 'due_date', 'estimate_hours', 'labels', 'position']);
        });

        Schema::dropIfExists('board_columns');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('syllabus_items');

        Schema::table('internships', function (Blueprint $table) {
            $table->dropIndex(['program', 'status']);
            $table->dropIndex(['supervisor_email']);
            $table->dropConstrainedForeignId('syllabus_reviewed_by');
            $table->dropColumn([
                'program', 'program_type', 'position', 'topic', 'organizer',
                'supervisor_name', 'supervisor_title', 'supervisor_email', 'supervisor_phone',
                'cadence', 'syllabus_status', 'syllabus_note', 'syllabus_reviewed_at',
            ]);
        });
    }
};
