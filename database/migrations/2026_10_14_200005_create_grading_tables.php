<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skala nilai huruf. Setiap perubahan membuat versi baru supaya nilai lama tidak ikut berubah.
        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('version');
            $table->string('grade', 3);
            // batas bawah nilai angka untuk huruf ini
            $table->decimal('min_score', 5, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['version', 'grade']);
            $table->index(['version', 'min_score']);
        });

        Schema::create('assessment_rubrics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('rubric_aspects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('assessment_rubrics')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            // bobot dalam persen; total satu rubrik harus 100 (dicek di aplikasi)
            $table->decimal('weight', 5, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['rubric_id', 'name']);
        });

        // Penilaian akhir satu penempatan: diisi mentor, divalidasi ketua divisi.
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('rubric_id')->constrained('assessment_rubrics')->restrictOnDelete();
            $table->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete();
            // draft | submitted | validated
            $table->string('status', 20)->default('draft')->index();
            $table->decimal('final_score', 5, 2)->nullable();
            // Terkunci permanen saat divalidasi.
            $table->string('letter_grade', 3)->nullable();
            $table->unsignedSmallInteger('grade_scale_version')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->text('supervisor_note')->nullable();
            $table->timestamps();
        });

        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rubric_aspect_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'rubric_aspect_id']);
        });

        // Nilai sidang KP, diinput kaprodi setelah masa magang selesai.
        Schema::create('defense_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->string('letter_grade', 3)->nullable();
            $table->unsignedSmallInteger('grade_scale_version')->nullable();
            $table->date('defended_at')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_scores');
        Schema::dropIfExists('assessment_scores');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('rubric_aspects');
        Schema::dropIfExists('assessment_rubrics');
        Schema::dropIfExists('grade_scales');
    }
};
