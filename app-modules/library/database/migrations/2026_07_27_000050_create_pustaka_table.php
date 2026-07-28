<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pustaka', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('kategori'); // sop | template | pedoman | onboarding | referensi
            $table->string('tipe');     // file | teks
            $table->string('path')->nullable();
            $table->longText('isi')->nullable();
            $table->unsignedInteger('versi')->default(1);
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pustaka');
    }
};
