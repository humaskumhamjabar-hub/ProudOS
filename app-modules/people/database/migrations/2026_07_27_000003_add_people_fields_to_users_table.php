<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained('roles');
            $table->date('aktif_mulai')->nullable()->after('role_id');
            $table->date('aktif_sampai')->nullable()->after('aktif_mulai'); // magang wajib diisi
            $table->foreignId('batch_id')->nullable()->after('aktif_sampai')->constrained('batches'); // null untuk pegawai tetap
            $table->string('status')->default('aktif')->after('batch_id'); // aktif | nonaktif
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('batch_id');
            $table->dropColumn(['aktif_mulai', 'aktif_sampai', 'status', 'deleted_at']);
        });
    }
};
