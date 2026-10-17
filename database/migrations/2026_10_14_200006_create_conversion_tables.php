<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kandidat yang direkomendasikan menjadi karyawan.
        Schema::create('conversion_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->unique()->constrained()->cascadeOnDelete();
            // direkomendasikan | ditawari | diterima | ditolak
            $table->string('status', 20)->default('direkomendasikan')->index();
            $table->string('offered_position', 100)->nullable();
            $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('conversion_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversion_candidate_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_histories');
        Schema::dropIfExists('conversion_candidates');
    }
};
