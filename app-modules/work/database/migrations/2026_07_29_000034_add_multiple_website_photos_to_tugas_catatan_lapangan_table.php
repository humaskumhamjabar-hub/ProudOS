<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->json('foto_website_items')->nullable()->after('foto_website_disimpan_at');
        });
    }

    public function down(): void
    {
        Schema::table('tugas_catatan_lapangan', function (Blueprint $table) {
            $table->dropColumn('foto_website_items');
        });
    }
};
