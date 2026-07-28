<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_visual', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('format');
            $table->string('rasio');
            $table->unsignedInteger('versi');
            $table->string('status')->default('draf');
            $table->unsignedInteger('durasi_per_slide_detik')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();
            $table->unique(['nama', 'versi']);
        });

        Schema::create('template_layout', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_visual_id')->constrained('template_visual')->cascadeOnDelete();
            $table->string('jenis');
            $table->json('definisi');
            $table->json('batas_karakter');
            $table->timestamps();
            $table->unique(['template_visual_id', 'jenis']);
        });

        Schema::create('template_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_visual_id')->constrained('template_visual')->cascadeOnDelete();
            $table->string('jenis');
            $table->string('path');
            $table->timestamps();
        });

        Schema::create('render', function (Blueprint $table) {
            $table->id();
            // Paket konten milik modul Content, jadi ID ini sengaja tanpa FK lintas modul.
            $table->unsignedBigInteger('paket_konten_id')->index();
            $table->foreignId('template_visual_id')->constrained('template_visual');
            $table->unsignedInteger('template_versi');
            $table->string('format');
            $table->string('status')->default('antre');
            $table->string('path_hasil')->nullable();
            $table->timestamp('dikerjakan_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->text('pesan_gagal')->nullable();
            $table->timestamps();
        });

        Schema::create('render_slide', function (Blueprint $table) {
            $table->id();
            $table->foreignId('render_id')->constrained('render')->cascadeOnDelete();
            $table->unsignedInteger('urutan');
            $table->string('jenis');
            // Bahan milik modul Content, jadi ID ini sengaja tanpa FK lintas modul.
            $table->unsignedBigInteger('bahan_id')->nullable()->index();
            $table->json('posisi_foto');
            $table->json('isi_teks');
            $table->timestamps();
            $table->unique(['render_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('render_slide');
        Schema::dropIfExists('render');
        Schema::dropIfExists('template_aset');
        Schema::dropIfExists('template_layout');
        Schema::dropIfExists('template_visual');
    }
};
