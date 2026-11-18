<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Proyek dan sertifikat portofolio bisa punya beberapa tautan (repo, demo, Figma, video, dokumen, kredensial, dll).
 * Tautan tunggal lama (url / credential_url) dipindahkan ke daftar tautan; kolom lama dibiarkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table) {
            $table->json('links')->nullable()->after('url');
        });
        Schema::table('portfolio_certificates', function (Blueprint $table) {
            $table->json('links')->nullable()->after('credential_url');
        });

        foreach (DB::table('portfolio_projects')->whereNotNull('url')->get(['id', 'url']) as $row) {
            DB::table('portfolio_projects')->where('id', $row->id)->update(['links' => json_encode([$row->url])]);
        }
        foreach (DB::table('portfolio_certificates')->whereNotNull('credential_url')->get(['id', 'credential_url']) as $row) {
            DB::table('portfolio_certificates')->where('id', $row->id)->update(['links' => json_encode([$row->credential_url])]);
        }
    }

    public function down(): void
    {
        Schema::table('portfolio_projects', fn (Blueprint $table) => $table->dropColumn('links'));
        Schema::table('portfolio_certificates', fn (Blueprint $table) => $table->dropColumn('links'));
    }
};
