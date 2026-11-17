<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal sidang/presentasi KP yang dibuat kaprodi: waktu, cara (luring/daring), ruang atau tautan, dan dosen penguji.
 * Satu jadwal per KP; nilai sidang tetap di tabel defense_scores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->unique()->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('mode', 10)->default('luring');     // luring | daring
            $table->string('place', 200);                      // ruang atau tautan rapat daring
            $table->foreignId('examiner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_schedules');
    }
};
