<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('brief')->nullable();
            $table->dateTime('deadline_at')->nullable();
            $table->string('status')->default('baru'); // baru | dikerjakan | selesai
            $table->unsignedBigInteger('agenda_id')->nullable(); // lintas modul: kolom biasa, tanpa relasi Eloquent
            $table->nullableMorphs('subjek'); // paket_konten | apa pun nanti
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->index('agenda_id');
        });

        Schema::create('tugas_bahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $table->string('path');
            $table->string('nama_asli');
            $table->string('mime');
            $table->foreignId('diunggah_oleh')->constrained('users');
            $table->timestamps();
        });

        Schema::create('tugas_komentar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('isi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas_komentar');
        Schema::dropIfExists('tugas_bahan');
        Schema::dropIfExists('tugas');
    }
};
