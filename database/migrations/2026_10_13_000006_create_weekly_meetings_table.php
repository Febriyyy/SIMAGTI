<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('week_number');
            // senin | selasa | rabu | kamis | jumat
            $table->string('day', 10);
            // format 24 jam, misalnya 10:00
            $table->string('time', 5);
            $table->string('url');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Satu meeting per mahasiswa per minggu
            $table->unique(['internship_id', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_meetings');
    }
};
