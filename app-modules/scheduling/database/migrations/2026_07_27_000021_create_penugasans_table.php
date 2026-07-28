<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penugasans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('tipe'); // berjam | berdeadline
            $table->dateTime('mulai_at')->nullable();    // diisi jika berjam
            $table->dateTime('selesai_at')->nullable();  // diisi jika berjam
            $table->dateTime('deadline_at')->nullable(); // diisi jika berdeadline
            $table->morphs('untuk'); // agenda | tugas | paket_konten
            $table->foreignId('peran_id')->constrained('peran_produksi');
            $table->foreignId('pembimbing_id')->nullable()->constrained('users'); // diisi bila pelaksananya magang
            $table->string('status')->default('aktif'); // aktif | butuh_pengganti | selesai | batal
            $table->foreignId('digantikan_dari_id')->nullable()->constrained('penugasans');
            $table->dateTime('dibaca_at')->nullable();   // otomatis saat halaman dibuka
            $table->dateTime('diterima_at')->nullable(); // saat tombol "terima" ditekan
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['mulai_at', 'selesai_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penugasans');
    }
};
