<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->json('carousel_sosmed_slides')->nullable()->after('dibuat_ai_sosmed_at');
            $table->timestamp('carousel_sosmed_disimpan_at')->nullable()->after('carousel_sosmed_slides');
        });
    }

    public function down(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->dropColumn(['carousel_sosmed_slides', 'carousel_sosmed_disimpan_at']);
        });
    }
};
