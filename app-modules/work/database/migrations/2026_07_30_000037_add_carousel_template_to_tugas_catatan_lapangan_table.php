<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            // ID template sengaja tanpa FK karena template dimiliki modul Visual.
            $table->unsignedBigInteger('carousel_sosmed_template_id')->nullable()->after('carousel_sosmed_disimpan_at');
            $table->unsignedInteger('carousel_sosmed_template_versi')->nullable()->after('carousel_sosmed_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->dropColumn(['carousel_sosmed_template_id', 'carousel_sosmed_template_versi']);
        });
    }
};
