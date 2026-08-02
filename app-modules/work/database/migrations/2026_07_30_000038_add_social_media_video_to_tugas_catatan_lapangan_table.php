<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->json('video_sosmed_scenes')->nullable()->after('carousel_sosmed_template_versi');
            // ID template sengaja tanpa FK karena template dimiliki modul Visual.
            $table->unsignedBigInteger('video_sosmed_template_id')->nullable()->after('video_sosmed_scenes');
            $table->unsignedInteger('video_sosmed_template_versi')->nullable()->after('video_sosmed_template_id');
            $table->string('video_sosmed_status')->nullable()->after('video_sosmed_template_versi');
            $table->string('video_sosmed_path')->nullable()->after('video_sosmed_status');
            $table->text('video_sosmed_pesan_gagal')->nullable()->after('video_sosmed_path');
            $table->timestamp('video_sosmed_disimpan_at')->nullable()->after('video_sosmed_pesan_gagal');
        });
    }

    public function down(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->dropColumn([
                'video_sosmed_scenes',
                'video_sosmed_template_id',
                'video_sosmed_template_versi',
                'video_sosmed_status',
                'video_sosmed_path',
                'video_sosmed_pesan_gagal',
                'video_sosmed_disimpan_at',
            ]);
        });
    }
};
