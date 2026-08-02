<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->longText('usulan_ai')->nullable()->after('draf_dasar_narasi');
            $table->string('model_ai')->nullable()->after('usulan_ai');
            $table->string('prompt_versi_ai')->nullable()->after('model_ai');
            $table->timestamp('dibuat_ai_at')->nullable()->after('prompt_versi_ai');
        });
    }

    public function down(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->dropColumn(['usulan_ai', 'model_ai', 'prompt_versi_ai', 'dibuat_ai_at']);
        });
    }
};
