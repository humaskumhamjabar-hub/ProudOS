<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->longText('bahan_sosmed')->nullable()->after('foto_website_items');
            $table->string('tautan_berita_sosmed', 2000)->nullable()->after('bahan_sosmed');
            $table->longText('caption_sosmed_final')->nullable()->after('tautan_berita_sosmed');
            $table->text('instruksi_koreksi_sosmed')->nullable()->after('caption_sosmed_final');
            $table->longText('usulan_ai_sosmed')->nullable()->after('instruksi_koreksi_sosmed');
            $table->string('model_ai_sosmed')->nullable()->after('usulan_ai_sosmed');
            $table->string('prompt_versi_ai_sosmed')->nullable()->after('model_ai_sosmed');
            $table->timestamp('dibuat_ai_sosmed_at')->nullable()->after('prompt_versi_ai_sosmed');
        });
    }

    public function down(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->dropColumn([
                'bahan_sosmed',
                'tautan_berita_sosmed',
                'caption_sosmed_final',
                'instruksi_koreksi_sosmed',
                'usulan_ai_sosmed',
                'model_ai_sosmed',
                'prompt_versi_ai_sosmed',
                'dibuat_ai_sosmed_at',
            ]);
        });
    }
};
