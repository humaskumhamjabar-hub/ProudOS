<?php

namespace App\Support;

use Modules\Visual\Models\TemplateVisual;

class PenempatanCarousel
{
    /** @return array<int, array<string, mixed>> */
    public static function bawaan(): array
    {
        return [
            [
                'foto_slots' => [
                    ['x' => 63, 'y' => 167, 'lebar' => 473, 'tinggi' => 573, 'radius' => 48],
                    ['x' => 559, 'y' => 167, 'lebar' => 472, 'tinggi' => 268, 'radius' => 47],
                    ['x' => 559, 'y' => 466, 'lebar' => 472, 'tinggi' => 274, 'radius' => 47],
                ],
                'teks' => ['x' => 63, 'y' => 916, 'lebar' => 959, 'tinggi' => 350],
            ],
            [
                'foto_slots' => [
                    ['x' => 139, 'y' => 187, 'lebar' => 857, 'tinggi' => 585, 'radius' => 47],
                ],
                'teks' => ['x' => 95, 'y' => 850, 'lebar' => 890, 'tinggi' => 372],
            ],
            [
                'foto_slots' => [
                    ['x' => 68, 'y' => 198, 'lebar' => 860, 'tinggi' => 574, 'radius' => 47],
                ],
                'teks' => ['x' => 95, 'y' => 850, 'lebar' => 890, 'tinggi' => 372],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function untukTemplate(?TemplateVisual $template, int $index): array
    {
        $bawaan = self::bawaan()[$index] ?? self::bawaan()[0];
        $layout = $template?->relationLoaded('layouts')
            ? $template->layouts->firstWhere('jenis', 'carousel_slide_'.($index + 1))
            : $template?->layouts()->where('jenis', 'carousel_slide_'.($index + 1))->first();

        return self::normalisasi(array_replace_recursive($bawaan, $layout?->definisi ?? []), $index);
    }

    /** @return array<string, mixed> */
    public static function normalisasi(array $penempatan, int $index): array
    {
        $bawaan = self::bawaan()[$index] ?? self::bawaan()[0];
        $jumlahSlot = $index === 0 ? 3 : 1;
        $fotoSlots = [];

        foreach (range(0, $jumlahSlot - 1) as $slot) {
            $fotoSlots[] = self::normalisasiKotak($penempatan['foto_slots'][$slot] ?? [], $bawaan['foto_slots'][$slot]);
        }

        return [
            'foto_slots' => $fotoSlots,
            'teks' => self::normalisasiKotak($penempatan['teks'] ?? [], $bawaan['teks']),
        ];
    }

    /** @return array{x: int, y: int, lebar: int, tinggi: int, radius?: int} */
    private static function normalisasiKotak(array $kotak, array $bawaan): array
    {
        $hasil = [
            'x' => max(0, min(1079, (int) ($kotak['x'] ?? $bawaan['x']))),
            'y' => max(0, min(1349, (int) ($kotak['y'] ?? $bawaan['y']))),
            'lebar' => max(40, min(1080, (int) ($kotak['lebar'] ?? $bawaan['lebar']))),
            'tinggi' => max(40, min(1350, (int) ($kotak['tinggi'] ?? $bawaan['tinggi']))),
        ];

        if (array_key_exists('radius', $bawaan)) {
            $hasil['radius'] = max(0, min(200, (int) ($kotak['radius'] ?? $bawaan['radius'])));
        }

        return $hasil;
    }
}
