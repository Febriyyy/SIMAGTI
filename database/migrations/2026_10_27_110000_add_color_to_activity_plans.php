<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Warna batang rencana kegiatan di Gantt, dipilih mahasiswa (biru, toska, hijau, kuning, ungu, merah muda, merah). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_plans', function (Blueprint $table) {
            $table->string('color', 12)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('activity_plans', fn (Blueprint $table) => $table->dropColumn('color'));
    }
};
