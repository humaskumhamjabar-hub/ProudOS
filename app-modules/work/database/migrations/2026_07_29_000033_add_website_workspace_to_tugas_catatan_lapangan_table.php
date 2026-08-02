<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->longText('bahan_website')->nullable()->after('draf_dasar_narasi');
            $table->longText('narasi_website_final')->nullable()->after('usulan_ai');
            $table->text('instruksi_koreksi_website')->nullable()->after('narasi_website_final');
            $table->unsignedBigInteger('foto_website_bahan_id')->nullable()->index()->after('dibuat_ai_at');
            $table->string('foto_website_path')->nullable()->after('foto_website_bahan_id');
            $table->string('foto_website_mime')->nullable()->after('foto_website_path');
            $table->timestamp('foto_website_disimpan_at')->nullable()->after('foto_website_mime');
        });
    }

    public function down(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->dropIndex(['foto_website_bahan_id']);
            $table->dropColumn([
                'bahan_website',
                'narasi_website_final',
                'instruksi_koreksi_website',
                'foto_website_bahan_id',
                'foto_website_path',
                'foto_website_mime',
                'foto_website_disimpan_at',
            ]);
        });
    }
};
