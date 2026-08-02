<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('konfigurasi_ai', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('nonaktif');
            $table->string('base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('timeout')->default(90);
            $table->string('prompt_versi')->default('berita-atensi-v1');
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('konfigurasi_ai');
    }
};
