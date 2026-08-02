<?php

namespace App\Support;

use Illuminate\Support\Str;
use Modules\Visual\Models\TemplateVisual;

class PenempatanVideoTemplate
{
    /** @return array<int, array<string, mixed>> */
    public static function bawaan(): array
    {
        return array_map(fn (int $index) => [
            'durasi' => $index === 0 ? 7 : 8,
            'layers' => self::layerBawaan($index),
        ], [0, 1, 2]);
    }

    /** @return array<string, mixed> */
    public static function untukTemplate(?TemplateVisual $template, int $index): array
    {
        $layout = $template?->relationLoaded('layouts')
            ? $template->layouts->firstWhere('jenis', 'video_scene_'.($index + 1))
            : $template?->layouts()->where('jenis', 'video_scene_'.($index + 1))->first();

        return self::normalisasi($layout?->definisi ?? self::bawaan()[$index], $index);
    }

    /** @return array<string, mixed> */
    public static function normalisasi(array $scene, int $index): array
    {
        $layers = collect($scene['layers'] ?? self::layerBawaan($index))
            ->filter(fn (mixed $layer) => is_array($layer))
            ->map(fn (array $layer, int $urutan) => self::normalisasiLayer($layer, $urutan))
            ->take(20)
            ->values()
            ->all();

        return [
            'durasi' => max(3, min(15, (int) ($scene['durasi'] ?? ($index === 0 ? 7 : 8)))),
            'layers' => $layers === [] ? self::layerBawaan($index) : $layers,
        ];
    }

    /** @return array<string, mixed> */
    public static function layerBaru(string $jenis, int $urutan): array
    {
        $jenis = in_array($jenis, ['png', 'foto', 'judul', 'paragraf'], true) ? $jenis : 'png';
        $nama = match ($jenis) {
            'foto' => 'Area foto',
            'judul' => 'Judul',
            'paragraf' => 'Paragraf',
            default => 'Elemen PNG',
        };

        return self::normalisasiLayer([
            'id' => 'layer_'.Str::lower(Str::random(8)),
            'nama' => $nama,
            'jenis' => $jenis,
            'x' => $jenis === 'foto' ? 70 : 90,
            'y' => match ($jenis) {
                'foto' => 260,
                'judul' => 1030,
                'paragraf' => 1280,
                default => 180,
            },
            'lebar' => $jenis === 'png' ? 900 : 940,
            'tinggi' => match ($jenis) {
                'foto' => 650,
                'judul' => 220,
                'paragraf' => 380,
                default => 180,
            },
            'urutan' => $urutan,
            'animasi' => $jenis === 'foto' ? 'fade_in' : 'masuk_kiri',
            'mulai' => min(4, $urutan * .3),
            'durasi_animasi' => .6,
        ], $urutan);
    }

    /** @return array<int, array<string, mixed>> */
    private static function layerBawaan(int $index): array
    {
        $isi = $index === 0
            ? [
                ['id' => 'background', 'nama' => 'Background', 'jenis' => 'png', 'x' => 0, 'y' => 0, 'lebar' => 1080, 'tinggi' => 1920, 'urutan' => 0, 'animasi' => 'diam', 'mulai' => 0, 'durasi_animasi' => 0],
                ['id' => 'header', 'nama' => 'Header / logo', 'jenis' => 'png', 'x' => 0, 'y' => 45, 'lebar' => 1080, 'tinggi' => 150, 'urutan' => 10, 'animasi' => 'fade_in', 'mulai' => .2, 'durasi_animasi' => .6],
                ['id' => 'foto', 'nama' => 'Area foto', 'jenis' => 'foto', 'x' => 70, 'y' => 245, 'lebar' => 940, 'tinggi' => 650, 'urutan' => 20, 'animasi' => 'fade_in', 'mulai' => .7, 'durasi_animasi' => .7],
                ['id' => 'tanggal', 'nama' => 'Kota dan tanggal', 'jenis' => 'judul', 'x' => 70, 'y' => 955, 'lebar' => 940, 'tinggi' => 90, 'urutan' => 30, 'animasi' => 'masuk_kiri', 'mulai' => 1.1, 'durasi_animasi' => .6],
                ['id' => 'judul', 'nama' => 'Judul utama', 'jenis' => 'judul', 'x' => 70, 'y' => 1060, 'lebar' => 940, 'tinggi' => 280, 'urutan' => 40, 'animasi' => 'naik', 'mulai' => 1.5, 'durasi_animasi' => .7],
                ['id' => 'subjudul', 'nama' => 'Subjudul', 'jenis' => 'paragraf', 'x' => 70, 'y' => 1365, 'lebar' => 940, 'tinggi' => 310, 'urutan' => 50, 'animasi' => 'fade_in', 'mulai' => 2.2, 'durasi_animasi' => .7],
                ['id' => 'footer', 'nama' => 'Footer', 'jenis' => 'png', 'x' => 0, 'y' => 1760, 'lebar' => 1080, 'tinggi' => 160, 'urutan' => 60, 'animasi' => 'fade_in', 'mulai' => .3, 'durasi_animasi' => .6],
            ]
            : [
                ['id' => 'background', 'nama' => 'Background', 'jenis' => 'png', 'x' => 0, 'y' => 0, 'lebar' => 1080, 'tinggi' => 1920, 'urutan' => 0, 'animasi' => 'diam', 'mulai' => 0, 'durasi_animasi' => 0],
                ['id' => 'header', 'nama' => 'Header / logo', 'jenis' => 'png', 'x' => 0, 'y' => 45, 'lebar' => 1080, 'tinggi' => 150, 'urutan' => 10, 'animasi' => 'fade_in', 'mulai' => .2, 'durasi_animasi' => .6],
                ['id' => 'foto', 'nama' => 'Area foto', 'jenis' => 'foto', 'x' => 70, 'y' => 245, 'lebar' => 940, 'tinggi' => 690, 'urutan' => 20, 'animasi' => 'fade_in', 'mulai' => .6, 'durasi_animasi' => .7],
                ['id' => 'paragraf_1', 'nama' => 'Paragraf pertama', 'jenis' => 'paragraf', 'x' => 70, 'y' => 1010, 'lebar' => 940, 'tinggi' => 300, 'urutan' => 30, 'animasi' => 'naik', 'mulai' => 1.1, 'durasi_animasi' => .7],
                ['id' => 'paragraf_2', 'nama' => 'Paragraf kedua', 'jenis' => 'paragraf', 'x' => 70, 'y' => 1330, 'lebar' => 940, 'tinggi' => 330, 'urutan' => 40, 'animasi' => 'naik', 'mulai' => 2, 'durasi_animasi' => .7],
                ['id' => 'footer', 'nama' => 'Footer', 'jenis' => 'png', 'x' => 0, 'y' => 1760, 'lebar' => 1080, 'tinggi' => 160, 'urutan' => 50, 'animasi' => 'fade_in', 'mulai' => .3, 'durasi_animasi' => .6],
            ];

        return array_map(fn (array $layer, int $urutan) => self::normalisasiLayer($layer, $urutan), $isi, array_keys($isi));
    }

    /** @return array<string, mixed> */
    private static function normalisasiLayer(array $layer, int $urutan): array
    {
        $jenis = in_array(($layer['jenis'] ?? ''), ['png', 'foto', 'judul', 'paragraf'], true) ? $layer['jenis'] : 'png';
        $animasi = in_array(($layer['animasi'] ?? ''), ['diam', 'fade_in', 'masuk_kiri', 'masuk_kanan', 'naik', 'zoom_lembut'], true)
            ? $layer['animasi']
            : 'fade_in';

        return [
            'id' => preg_replace('/[^a-z0-9_]/', '', Str::lower((string) ($layer['id'] ?? "layer_{$urutan}"))) ?: "layer_{$urutan}",
            'nama' => mb_substr(trim((string) ($layer['nama'] ?? 'Layer')), 0, 60),
            'jenis' => $jenis,
            'x' => max(-1080, min(1079, (int) ($layer['x'] ?? 0))),
            'y' => max(-1920, min(1919, (int) ($layer['y'] ?? 0))),
            'lebar' => max(40, min(1080, (int) ($layer['lebar'] ?? 400))),
            'tinggi' => max(40, min(1920, (int) ($layer['tinggi'] ?? 200))),
            'urutan' => max(0, min(100, (int) ($layer['urutan'] ?? $urutan * 10))),
            'animasi' => $animasi,
            'mulai' => max(0, min(15, round((float) ($layer['mulai'] ?? 0), 1))),
            'durasi_animasi' => max(0, min(3, round((float) ($layer['durasi_animasi'] ?? .6), 1))),
        ];
    }
}
