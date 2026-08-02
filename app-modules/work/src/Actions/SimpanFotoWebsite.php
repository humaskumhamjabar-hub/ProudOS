<?php

namespace Modules\Work\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Work\Models\TugasBahan;
use RuntimeException;

class SimpanFotoWebsite
{
    /** @return array{path: string, mime: string} */
    public function handle(TugasBahan $bahan, int $tugasId, int $userId, float $zoom, int $fokusX, int $fokusY, int $rotasi = 0): array
    {
        abort_unless($bahan->tugas_id === $tugasId && str_starts_with($bahan->mime, 'image/'), 422);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($bahan->path), 404);

        $sumber = $this->bukaGambar($disk->path($bahan->path), $bahan->mime);
        $rotasi = max(-180, min(180, $rotasi));
        $lebarSumber = imagesx($sumber);
        $tinggiSumber = imagesy($sumber);
        $lebarTujuan = 1050;
        $tinggiTujuan = 750;
        $radian = deg2rad(abs($rotasi));
        $skalaAmanRotasi = abs(cos($radian)) + ($lebarTujuan / $tinggiTujuan * abs(sin($radian)));
        $lebarPutar = (int) ceil($lebarTujuan * $skalaAmanRotasi);
        $tinggiPutar = (int) ceil($tinggiTujuan * $skalaAmanRotasi);

        $skalaDasar = max($lebarPutar / $lebarSumber, $tinggiPutar / $tinggiSumber);
        $skala = $skalaDasar * max(1, min(3, $zoom));
        $lebarPotong = min($lebarSumber, $lebarPutar / $skala);
        $tinggiPotong = min($tinggiSumber, $tinggiPutar / $skala);
        $x = ($lebarSumber - $lebarPotong) * max(0, min(100, $fokusX)) / 100;
        $y = ($tinggiSumber - $tinggiPotong) * max(0, min(100, $fokusY)) / 100;

        $siapPutar = imagecreatetruecolor($lebarPutar, $tinggiPutar);
        imagecopyresampled(
            $siapPutar,
            $sumber,
            0,
            0,
            (int) round($x),
            (int) round($y),
            $lebarPutar,
            $tinggiPutar,
            (int) round($lebarPotong),
            (int) round($tinggiPotong),
        );
        imagedestroy($sumber);

        $hasilPutar = $rotasi === 0 ? $siapPutar : imagerotate($siapPutar, -$rotasi, 0);
        throw_unless($hasilPutar instanceof \GdImage, RuntimeException::class, 'Foto website tidak dapat diputar.');
        if ($hasilPutar !== $siapPutar) {
            imagedestroy($siapPutar);
        }

        $hasil = imagecreatetruecolor($lebarTujuan, $tinggiTujuan);
        $xHasil = max(0, (imagesx($hasilPutar) - $lebarTujuan) / 2);
        $yHasil = max(0, (imagesy($hasilPutar) - $tinggiTujuan) / 2);
        imagecopy(
            $hasil,
            $hasilPutar,
            0,
            0,
            (int) round($xHasil),
            (int) round($yHasil),
            $lebarTujuan,
            $tinggiTujuan,
        );

        ob_start();
        imagejpeg($hasil, null, 88);
        $isi = ob_get_clean();

        imagedestroy($hasilPutar);
        imagedestroy($hasil);

        if (! is_string($isi)) {
            throw new RuntimeException('Foto website tidak dapat disimpan.');
        }

        $path = "tugas-website/{$tugasId}/{$userId}/foto-website-{$bahan->id}.jpg";
        $disk->put($path, $isi);

        return ['path' => $path, 'mime' => 'image/jpeg'];
    }

    private function bukaGambar(string $path, string $mime): \GdImage
    {
        $gambar = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => false,
        };

        if (! $gambar instanceof \GdImage) {
            throw new RuntimeException('Format foto tidak dapat diproses. Gunakan JPG, PNG, atau WebP.');
        }

        return $gambar;
    }
}
