<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Portofolio menjadi milik mahasiswa (bukan milik KP), bisa diisi sebelum mendapat penempatan:
 * minat, IPK (ditampilkan bila diizinkan), proyek yang pernah dibuat, dan sertifikat pelatihan.
 * Skill memakai tabel student_skills yang sudah ada (dengan tingkat kemahiran).
 * Kolom lama (internship_id, task_ids, show_grade) dibiarkan agar data lama tidak hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->json('interests')->nullable()->after('skills');
            $table->boolean('show_gpa')->default(false)->after('show_grade');
        });

        // Isi pemilik dari KP lama; bila satu mahasiswa punya lebih dari satu portofolio, yang terbaru dipakai.
        $rows = DB::table('portfolios')
            ->join('internships', 'internships.id', '=', 'portfolios.internship_id')
            ->orderByDesc('portfolios.updated_at')
            ->get(['portfolios.id', 'portfolios.skills', 'internships.student_id']);
        $seen = [];
        foreach ($rows as $row) {
            if (isset($seen[$row->student_id])) {
                continue;
            }
            $seen[$row->student_id] = true;
            DB::table('portfolios')->where('id', $row->id)->update(['user_id' => $row->student_id]);

            // Keahlian lama (teks bebas) dipindahkan ke daftar skill mahasiswa.
            foreach ((array) json_decode((string) $row->skills, true) as $name) {
                $name = mb_substr(trim((string) $name), 0, 80);
                if ($name === '') {
                    continue;
                }
                $skillId = DB::table('skills')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->value('id')
                    ?? DB::table('skills')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
                $exists = DB::table('student_skills')->where('user_id', $row->student_id)->where('skill_id', $skillId)->exists();
                if (! $exists) {
                    DB::table('student_skills')->insert(['user_id' => $row->student_id, 'skill_id' => $skillId, 'level' => 'menengah', 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }

        Schema::table('portfolios', function (Blueprint $table) {
            $table->unique('user_id');
        });
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropForeign(['internship_id']);
        });
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropUnique(['internship_id']);
        });
        Schema::table('portfolios', function (Blueprint $table) {
            $table->unsignedBigInteger('internship_id')->nullable()->change();
            $table->foreign('internship_id')->references('id')->on('internships')->nullOnDelete();
        });

        Schema::create('portfolio_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('role', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('tech', 200)->nullable();
            $table->string('url', 255)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->foreignId('image_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('portfolio_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('issuer', 120);
            $table->date('issued_on')->nullable();
            $table->string('credential_url', 255)->nullable();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_certificates');
        Schema::dropIfExists('portfolio_projects');
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['interests', 'show_gpa']);
        });
    }
};
