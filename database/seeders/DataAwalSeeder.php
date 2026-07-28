<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DataAwalSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ([
            ['nama' => 'Koordinator', 'slug' => 'koordinator'],
            ['nama' => 'Staf', 'slug' => 'staf'],
            ['nama' => 'Magang', 'slug' => 'magang'],
            ['nama' => 'Admin', 'slug' => 'admin'],
        ] as $role) {
            DB::table('roles')->updateOrInsert(['slug' => $role['slug']], $role + ['created_at' => $now, 'updated_at' => $now]);
        }

        foreach ([
            ['nama' => 'Kelola pengguna', 'slug' => 'kelola_pengguna'],
            ['nama' => 'Kelola agenda', 'slug' => 'kelola_agenda'],
            ['nama' => 'Kelola PR Plan', 'slug' => 'kelola_pr_plan'],
            ['nama' => 'Kelola konten', 'slug' => 'kelola_konten'],
            ['nama' => 'Kelola penugasan', 'slug' => 'kelola_penugasan'],
            ['nama' => 'Kelola tugas', 'slug' => 'kelola_tugas'],
            ['nama' => 'Upload publikasi', 'slug' => 'upload_publikasi'],
            ['nama' => 'Kelola pustaka', 'slug' => 'kelola_pustaka'],
            ['nama' => 'Kelola template visual', 'slug' => 'kelola_template_visual'],
            ['nama' => 'Lihat laporan', 'slug' => 'lihat_laporan'],
        ] as $perm) {
            DB::table('permissions')->updateOrInsert(['slug' => $perm['slug']], $perm + ['created_at' => $now, 'updated_at' => $now]);
        }

        $izinPerRole = [
            'admin' => ['kelola_pengguna', 'kelola_agenda', 'kelola_pr_plan', 'kelola_konten', 'kelola_penugasan', 'kelola_tugas', 'upload_publikasi', 'kelola_pustaka', 'kelola_template_visual', 'lihat_laporan'],
            'koordinator' => ['kelola_agenda', 'kelola_pr_plan', 'kelola_konten', 'kelola_penugasan', 'kelola_tugas', 'upload_publikasi', 'kelola_pustaka', 'lihat_laporan'],
            'staf' => ['upload_publikasi'],
            'magang' => [],
        ];

        foreach ($izinPerRole as $roleSlug => $permSlugs) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            foreach ($permSlugs as $permSlug) {
                $permId = DB::table('permissions')->where('slug', $permSlug)->value('id');
                DB::table('role_permission')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $permId]);
            }
        }

        foreach ([
            ['nama' => 'Peliput', 'slug' => 'peliput'],
            ['nama' => 'Fotografer', 'slug' => 'fotografer'],
            ['nama' => 'Videographer', 'slug' => 'videographer'],
            ['nama' => 'Penulis Script', 'slug' => 'penulis_script'],
            ['nama' => 'Penulis Berita', 'slug' => 'penulis_berita'],
            ['nama' => 'Editor', 'slug' => 'editor'],
            ['nama' => 'Desainer', 'slug' => 'desainer'],
            ['nama' => 'Voice Over', 'slug' => 'voice_over'],
            ['nama' => 'Pendamping', 'slug' => 'pendamping'],
        ] as $peran) {
            DB::table('peran_produksi')->updateOrInsert(['slug' => $peran['slug']], $peran + ['aktif' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        foreach ([
            ['nama' => 'Instagram Kemenkum Jabar', 'jenis' => 'instagram'],
            ['nama' => 'TikTok Kemenkum Jabar', 'jenis' => 'tiktok'],
            ['nama' => 'Website Kemenkum Jabar', 'jenis' => 'website'],
            ['nama' => 'X Kemenkum Jabar', 'jenis' => 'x'],
            ['nama' => 'YouTube Kemenkum Jabar', 'jenis' => 'youtube'],
        ] as $kanal) {
            DB::table('kanal')->updateOrInsert(['nama' => $kanal['nama']], $kanal + ['aktif' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        foreach ([
            ['nama' => 'Berita web', 'slug' => 'berita-web'],
            ['nama' => 'Caption media sosial', 'slug' => 'caption-media-sosial'],
            ['nama' => 'Carousel', 'slug' => 'carousel'],
            ['nama' => 'Video pendek', 'slug' => 'video-pendek'],
            ['nama' => 'Infografis', 'slug' => 'infografis'],
        ] as $output) {
            DB::table('jenis_outputs')->updateOrInsert(
                ['slug' => $output['slug']],
                $output + ['aktif' => true, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        $adminId = DB::table('users')->orderBy('id')->value('id');

        if ($adminId) {
            $templateId = DB::table('template_visual')->where('nama', 'Editorial Kanwil')->where('versi', 1)->value('id');

            if (! $templateId) {
                $templateId = DB::table('template_visual')->insertGetId([
                    'nama' => 'Editorial Kanwil',
                    'format' => 'ig_carousel',
                    'rasio' => '4:5',
                    'versi' => 1,
                    'status' => 'aktif',
                    'dibuat_oleh' => $adminId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ([
                ['jenis' => 'cover', 'batas' => ['kicker' => 32, 'judul' => 90, 'isi' => 0]],
                ['jenis' => 'isi', 'batas' => ['kicker' => 32, 'judul' => 72, 'isi' => 280]],
            ] as $layout) {
                DB::table('template_layout')->updateOrInsert(
                    ['template_visual_id' => $templateId, 'jenis' => $layout['jenis']],
                    [
                        'definisi' => json_encode(['tema' => 'editorial-kanwil']),
                        'batas_karakter' => json_encode($layout['batas']),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                );
            }
        }
    }
}
