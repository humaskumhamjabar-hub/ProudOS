<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Data master dipanggil lagi setelah admin ada supaya template visual
        // mendapat pembuat tanpa menyimpan FK lintas modul di kode modul.
        $this->call(DataAwalSeeder::class);

        // Satu akun admin awal — ganti kata sandi lewat .env saat produksi.
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');

        DB::table('users')->updateOrInsert(
            ['email' => 'admin@proud.test'],
            [
                'nama' => 'Admin PROUD',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'role_id' => $adminRoleId,
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $this->call(DataAwalSeeder::class);
    }
}
