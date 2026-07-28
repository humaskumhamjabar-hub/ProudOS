<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kanal', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('jenis'); // instagram | tiktok | website | x | youtube — data, bukan enum
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('publikasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paket_konten_id')->nullable(); // lintas modul (content, Rilis 2): kolom biasa
            $table->foreignId('kanal_id')->constrained('kanal');
            $table->dateTime('tayang_at');
            $table->string('url');
            $table->string('evidence_path')->nullable(); // tangkapan layar
            $table->foreignId('pic_id')->constrained('users');
            $table->boolean('diubah_setelah_tayang')->default(false);
            $table->text('alasan_perubahan')->nullable();
            $table->string('diminta_oleh')->nullable(); // untuk revisi dari pimpinan
            $table->timestamps();

            $table->index('paket_konten_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publikasi');
        Schema::dropIfExists('kanal');
    }
};
