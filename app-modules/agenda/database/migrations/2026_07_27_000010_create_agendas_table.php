<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->dateTime('mulai_at');   // jam penting, bukan hanya tanggal
            $table->dateTime('selesai_at')->nullable();
            $table->string('lokasi')->nullable();
            $table->json('kebutuhan_humas')->nullable(); // foto, video, berita, caption
            $table->nullableMorphs('sumber'); // pr_plan_item | jadwal_harian | manual — hanya jejak
            $table->string('status')->default('rencana'); // rencana | berjalan | selesai | batal
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
