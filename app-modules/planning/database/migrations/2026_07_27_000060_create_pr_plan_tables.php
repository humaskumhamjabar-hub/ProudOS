<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_outputs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('pr_plans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('tema')->nullable();
            $table->date('periode_mulai');
            $table->date('periode_selesai');
            $table->unsignedInteger('target_jumlah_konten');
            $table->string('status')->default('draf');
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();
        });

        Schema::create('pr_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pr_plan_id')->constrained('pr_plans')->cascadeOnDelete();
            $table->string('judul');
            $table->text('catatan')->nullable();
            $table->string('rencana_kasar')->nullable();
            $table->foreignId('jenis_output_id')->constrained('jenis_outputs');
            $table->json('kanal_tujuan')->nullable();
            // Lintas modul Agenda: ID biasa, tanpa relasi Eloquent atau FK lintas modul.
            $table->unsignedBigInteger('agenda_id')->nullable()->index();
            $table->string('status')->default('ide');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_plan_items');
        Schema::dropIfExists('pr_plans');
        Schema::dropIfExists('jenis_outputs');
    }
};
