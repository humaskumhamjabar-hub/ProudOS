<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $table->longText('laporan_atensi')->nullable();
            $table->longText('sambutan')->nullable();
            $table->longText('draf_dasar_narasi')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->unique(['tugas_id', 'dibuat_oleh']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas_catatan_lapangan');
    }
};
