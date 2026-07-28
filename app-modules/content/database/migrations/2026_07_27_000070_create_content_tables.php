<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paket_konten', function (Blueprint $table) {
            $table->id();
            // Jejak lintas modul disimpan sebagai ID biasa, tanpa relasi Eloquent atau FK.
            $table->unsignedBigInteger('agenda_id')->nullable()->index();
            $table->unsignedBigInteger('pr_plan_item_id')->nullable()->unique();
            $table->string('judul');
            $table->string('subjudul')->nullable();
            $table->string('status')->default('on_progress');
            $table->unsignedInteger('revisi_ke')->default(0);
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();
        });

        Schema::create('bahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_konten_id')->constrained('paket_konten')->cascadeOnDelete();
            $table->string('tipe');
            $table->string('path');
            $table->string('nama_asli');
            $table->string('mime')->nullable();
            $table->longText('teks_terekstrak')->nullable();
            $table->string('status_ekstraksi')->default('menunggu');
            $table->boolean('dipakai_final')->default(false);
            $table->foreignId('diunggah_oleh')->constrained('users');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('draf', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_konten_id')->constrained('paket_konten')->cascadeOnDelete();
            $table->string('jenis');
            $table->longText('isi');
            $table->unsignedInteger('versi');
            $table->string('asal')->default('manusia');
            $table->boolean('latihan')->default(false);
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();
            $table->unique(['paket_konten_id', 'jenis', 'versi']);
        });

        Schema::create('catatan_pembimbing', function (Blueprint $table) {
            $table->id();
            // Penugasan milik modul Scheduling, jadi ID ini sengaja bukan FK lintas modul.
            $table->unsignedBigInteger('penugasan_id')->index();
            $table->text('isi');
            $table->foreignId('oleh_id')->constrained('users');
            $table->timestamps();
        });

        Schema::create('ai_usulan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paket_konten_id')->constrained('paket_konten')->cascadeOnDelete();
            $table->string('jenis');
            $table->longText('isi');
            $table->string('status')->default('menunggu');
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users');
            $table->timestamp('ditinjau_at')->nullable();
            $table->string('model')->nullable();
            $table->string('prompt_versi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usulan');
        Schema::dropIfExists('catatan_pembimbing');
        Schema::dropIfExists('draf');
        Schema::dropIfExists('bahan');
        Schema::dropIfExists('paket_konten');
    }
};
