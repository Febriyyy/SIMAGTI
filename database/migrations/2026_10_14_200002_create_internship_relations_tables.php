<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mentor dan ketua divisi diatur per penempatan.
        Schema::create('internship_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // mentor | ketua_divisi
            $table->string('role', 20);
            $table->timestamps();

            $table->unique(['internship_id', 'user_id', 'role']);
            $table->index(['user_id', 'role']);
        });

        // Alur persetujuan KP mandiri: verifikasi biro, lalu persetujuan kaprodi.
        Schema::create('internship_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            // biro_verification | kaprodi_approval
            $table->string('step', 30);
            // pending | approved | rejected
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['internship_id', 'step']);
        });

        Schema::create('internship_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['internship_id', 'created_at']);
        });

        // Undangan email untuk mentor/ketua divisi di tempat KP mandiri.
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            // mentor | ketua_divisi
            $table->string('role', 20);
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('internship_status_histories');
        Schema::dropIfExists('internship_approvals');
        Schema::dropIfExists('internship_staff');
    }
};
