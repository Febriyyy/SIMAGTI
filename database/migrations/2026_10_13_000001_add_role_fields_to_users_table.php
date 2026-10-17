<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // mahasiswa | mentor | ketua_divisi | dospem | kaprodi | biro
            $table->string('role', 20)->default('mahasiswa')->index()->after('password');
            $table->string('nim', 20)->nullable()->unique()->after('role');
            $table->string('phone', 20)->nullable()->after('nim');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropUnique(['nim']);
            $table->dropSoftDeletes();
            $table->dropColumn(['role', 'nim', 'phone', 'is_active', 'last_login_at']);
        });
    }
};
