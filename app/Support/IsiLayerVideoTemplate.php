<?php

namespace App\Support;

class IsiLayerVideoTemplate
{
    /** @param array<string, mixed> $slide */
    public static function teks(array $layer, array $slide): string
    {
        $id = (string) ($layer['id'] ?? '');
        $paragraf = preg_split('/\R\s*\R/u', trim((string) ($slide['isi'] ?? ''))) ?: [];

        if ($id === 'tanggal') {
            return trim(implode(' · ', array_filter([$slide['kota'] ?? null, $slide['tanggal'] ?? null])));
        }
        if ($id === 'judul') {
            return (string) ($slide['judul'] ?? '');
        }
        if ($id === 'subjudul') {
            return (string) ($slide['isi'] ?? '');
        }
        if (preg_match('/paragraf_(\d+)/', $id, $cocok)) {
            return (string) ($paragraf[((int) $cocok[1]) - 1] ?? $slide['isi'] ?? '');
        }

        return ($layer['jenis'] ?? '') === 'judul'
            ? (string) ($slide['judul'] ?? '')
            : (string) ($slide['isi'] ?? '');
    }
}
