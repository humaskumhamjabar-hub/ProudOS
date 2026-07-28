<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temuan', function (Blueprint $table) {
            $table->id();
            $table->string('sumber');
            $table->text('ringkasan');
            $table->text('url')->nullable();
            $table->string('sentimen');
            $table->date('tanggal');
            $table->string('status_tindak_lanjut')->default('baru');
            // User milik modul People, ID disimpan tanpa relasi Eloquent lintas modul.
            $table->unsignedBigInteger('pic_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('tindak_lanjut', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temuan_id')->constrained('temuan')->cascadeOnDelete();
            $table->text('aksi');
            // User milik modul People, ID disimpan tanpa relasi Eloquent lintas modul.
            $table->unsignedBigInteger('oleh_id')->index();
            $table->timestamp('at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tindak_lanjut');
        Schema::dropIfExists('temuan');
    }
};
