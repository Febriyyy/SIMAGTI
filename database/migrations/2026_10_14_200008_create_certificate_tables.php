<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Template dari kampus, atau milik perusahaan sendiri (logo, tanda tangan, stempel).
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            // kampus | perusahaan
            $table->string('scope', 20)->default('kampus');
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('logo_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('signature_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('stamp_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('signer_name')->nullable();
            $table->string('signer_title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('certificate_templates')->nullOnDelete();
            $table->string('number', 60)->unique();
            // dipakai pada QR untuk verifikasi publik
            $table->string('verification_token', 64)->unique();
            // draft | terbit | dicabut
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revoked_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('certificate_templates');
    }
};
