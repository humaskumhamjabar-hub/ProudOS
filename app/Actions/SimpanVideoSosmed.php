<?php

namespace App\Actions;

use App\Support\IsiLayerVideoTemplate;
use App\Support\PenempatanVideoTemplate;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Support\Facades\Storage;
use Modules\Visual\Models\TemplateVisual;
use Modules\Work\Models\TugasBahan;
use RuntimeException;

class SimpanVideoSosmed
{
    public function __construct(
        private readonly ProcessFactory $process,
    ) {}

    /**
     * @param  array<int, array{urutan: int, durasi: int, gerakan: string}>  $scenes
     * @param  array<int, array<string, mixed>>  $carouselSlides
     */
    public function handle(array $scenes, array $carouselSlides, int $tugasId, int $userId, ?TemplateVisual $template = null): string
    {
        throw_unless(count($scenes) === 3 && count($carouselSlides) === 3, RuntimeException::class, 'Video membutuhkan tepat tiga slide carousel.');

        $ffmpeg = (string) config('visual.ffmpeg_path');
        if ($ffmpeg === '' || ! is_executable($ffmpeg)) {
            throw new RuntimeException('FFmpeg belum tersedia di server.');
        }

        $disk = Storage::disk('local');
        $direktoriRelatif = "tugas-sosmed/{$tugasId}/{$userId}/video";
        $disk->makeDirectory($direktoriRelatif);
        $direktori = $disk->path($direktoriRelatif);
        $klip = [];
        $durasi = [];
        $template?->loadMissing(['layouts', 'aset']);

        foreach ($scenes as $index => $scene) {
            $urutan = (int) ($scene['urutan'] ?? $index + 1);
            $slide = collect($carouselSlides)->first(fn (array $item) => (int) ($item['urutan'] ?? 0) === $urutan);
            $pathSlide = is_array($slide) ? ($slide['path'] ?? null) : null;
            throw_unless(is_string($pathSlide) && $disk->exists($pathSlide), RuntimeException::class, "Hasil carousel slide {$urutan} tidak ditemukan.");

            $pathSumber = $disk->path($pathSlide);
            if ($template) {
                $pathSumber = $this->gambarSumberTemplate($slide, $tugasId) ?? $pathSumber;
            }

            $detik = max(1, min(15, (int) ($scene['durasi'] ?? 8)));
            $pathKlip = "{$direktori}/scene-".str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'.mp4';
            if ($template) {
                $this->bangunKlipTemplate($ffmpeg, $template, $index, $slide, $pathSumber, $direktori, $pathKlip, $detik);
            } else {
                $jumlahFrame = $detik * 30;
                $gerakan = (string) ($scene['gerakan'] ?? 'zoom_masuk');
                [$zoom, $x, $y] = $this->ekspresiGerakan($gerakan, $jumlahFrame);
                $filter = "scale=1080:1350:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2:color=0xf8f8f6,zoompan=z='{$zoom}':x='{$x}':y='{$y}':d={$jumlahFrame}:s=1080x1920:fps=30,format=yuv420p";
                $this->process->timeout(180)->run([
                    $ffmpeg, '-y', '-loop', '1', '-framerate', '30', '-i', $pathSumber,
                    '-t', (string) $detik, '-vf', $filter, '-r', '30', '-an', '-c:v', 'libx264', '-preset', 'veryfast',
                    '-pix_fmt', 'yuv420p', '-movflags', '+faststart', $pathKlip,
                ])->throw();
            }
            $klip[] = $pathKlip;
            $durasi[] = $detik;
        }

        $hasilRelatif = "{$direktoriRelatif}/video-sosmed.mp4";
        $hasil = $disk->path($hasilRelatif);
        $transisi = 0.6;
        $offsetKedua = $durasi[0] - $transisi;
        $offsetKetiga = $durasi[0] + $durasi[1] - ($transisi * 2);
        $this->process->timeout(300)->run([
            $ffmpeg, '-y',
            '-i', $klip[0], '-i', $klip[1], '-i', $klip[2],
            '-filter_complex', "[0:v][1:v]xfade=transition=fade:duration={$transisi}:offset={$offsetKedua}[gabung1];[gabung1][2:v]xfade=transition=fade:duration={$transisi}:offset={$offsetKetiga},format=yuv420p[hasil]",
            '-map', '[hasil]', '-an', '-r', '30', '-c:v', 'libx264', '-preset', 'veryfast',
            '-pix_fmt', 'yuv420p', '-movflags', '+faststart', $hasil,
        ])->throw();
        throw_unless(is_file($hasil) && filesize($hasil) > 0, RuntimeException::class, 'FFmpeg tidak menghasilkan video.');

        return $hasilRelatif;
    }

    /** @param array<string, mixed> $slide */
    private function gambarSumberTemplate(array $slide, int $tugasId): ?string
    {
        $bahanId = (int) ($slide['bahan_id'] ?? $slide['foto_slots'][0]['bahan_id'] ?? 0);
        if ($bahanId < 1) {
            return null;
        }

        $bahan = TugasBahan::query()
            ->where('tugas_id', $tugasId)
            ->whereKey($bahanId)
            ->first();

        return $bahan && Storage::disk('local')->exists($bahan->path)
            ? Storage::disk('local')->path($bahan->path)
            : null;
    }

    /** @param array<string, mixed> $slide */
    private function bangunKlipTemplate(string $ffmpeg, TemplateVisual $template, int $index, array $slide, string $gambar, string $direktori, string $pathKlip, int $detik): void
    {
        $scene = PenempatanVideoTemplate::untukTemplate($template, $index);
        $perintah = [$ffmpeg, '-y', '-f', 'lavfi', '-i', "color=c=0xf8f8f6:s=1080x1920:r=30:d={$detik}"];
        $filter = ['[0:v]format=rgba[dasar0]'];
        $dasar = 'dasar0';
        $input = 1;

        foreach (collect($scene['layers'] ?? [])->sortBy('urutan')->values() as $urutanLayer => $layer) {
            $sumber = $this->sumberLayer($template, $index, $layer, $slide, $gambar, $direktori);
            if (! $sumber) {
                continue;
            }

            $perintah = [...$perintah, '-loop', '1', '-framerate', '30', '-i', $sumber];
            $lebar = max(1, (int) $layer['lebar']);
            $tinggi = max(1, (int) $layer['tinggi']);
            $namaLayer = "layer{$urutanLayer}";
            $filterLayer = $layer['jenis'] === 'foto'
                ? "[{$input}:v]scale={$lebar}:{$tinggi}:force_original_aspect_ratio=increase,crop={$lebar}:{$tinggi},format=rgba"
                : "[{$input}:v]scale={$lebar}:{$tinggi}:force_original_aspect_ratio=decrease,pad={$lebar}:{$tinggi}:(ow-iw)/2:(oh-ih)/2:color=0x00000000,format=rgba";
            $mulai = max(0, (float) ($layer['mulai'] ?? 0));
            $durasiAnimasi = max(.01, (float) ($layer['durasi_animasi'] ?? 0));
            if (($layer['animasi'] ?? 'diam') !== 'diam') {
                $filterLayer .= ",fade=t=in:st={$mulai}:d={$durasiAnimasi}:alpha=1";
            }
            if (($layer['animasi'] ?? 'diam') === 'zoom_lembut') {
                $progresZoom = "min(1,max(0,(t-{$mulai})/{$durasiAnimasi}))";
                $filterLayer .= ",scale=w='iw*(0.94+0.06*{$progresZoom})':h='ih*(0.94+0.06*{$progresZoom})':eval=frame,pad={$lebar}:{$tinggi}:(ow-iw)/2:(oh-ih)/2:color=0x00000000";
            }
            $filter[] = $filterLayer."[{$namaLayer}]";

            [$x, $y] = $this->posisiAnimasiLayer($layer, $mulai, $durasiAnimasi);
            $hasil = 'dasar'.($urutanLayer + 1);
            $filter[] = "[{$dasar}][{$namaLayer}]overlay=x='{$x}':y='{$y}':shortest=1:eof_action=pass:format=auto:eval=frame[{$hasil}]";
            $dasar = $hasil;
            $input++;
        }

        $filter[] = "[{$dasar}]format=yuv420p[hasil]";
        $perintah = [...$perintah,
            '-filter_complex', implode(';', $filter), '-map', '[hasil]', '-t', (string) $detik,
            '-r', '30', '-an', '-c:v', 'libx264', '-preset', 'veryfast', '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart', $pathKlip,
        ];
        $this->process->timeout(240)->run($perintah)->throw();
        throw_unless(is_file($pathKlip) && filesize($pathKlip) > 0, RuntimeException::class, 'Scene template video tidak dapat dibuat.');

        $poster = "{$direktori}/template-scene-".($index + 1).'.png';
        $this->process->timeout(60)->run([$ffmpeg, '-y', '-i', $pathKlip, '-frames:v', '1', '-update', '1', $poster])->throw();
    }

    /** @param array<string, mixed> $layer @param array<string, mixed> $slide */
    private function sumberLayer(TemplateVisual $template, int $sceneIndex, array $layer, array $slide, string $gambar, string $direktori): ?string
    {
        if ($layer['jenis'] === 'foto') {
            return $gambar;
        }
        if ($layer['jenis'] === 'png') {
            $jenis = 'video_scene_'.($sceneIndex + 1).'_'.$layer['id'];
            $aset = $template->aset->firstWhere('jenis', $jenis);

            return $aset && Storage::disk('local')->exists($aset->path)
                ? Storage::disk('local')->path($aset->path)
                : null;
        }
        if (! in_array($layer['jenis'], ['judul', 'paragraf'], true)) {
            return null;
        }

        $path = "{$direktori}/teks-scene-".($sceneIndex + 1).'-'.$layer['id'].'.png';
        $teks = IsiLayerVideoTemplate::teks($layer, $slide);
        $this->buatPngTeks($layer, $teks, $path);

        return $path;
    }

    /** @param array<string, mixed> $layer @return array{string, string} */
    private function posisiAnimasiLayer(array $layer, float $mulai, float $durasi): array
    {
        $x = (int) $layer['x'];
        $y = (int) $layer['y'];
        $lebar = (int) $layer['lebar'];
        $tinggi = (int) $layer['tinggi'];
        $progres = "if(lt(t,{$mulai}),0,if(gte(t,".($mulai + $durasi)."),1,(t-{$mulai})/{$durasi}))";

        return match ($layer['animasi'] ?? 'diam') {
            'masuk_kiri' => ["{$x}-{$lebar}*0.18*(1-{$progres})", (string) $y],
            'masuk_kanan' => ["{$x}+{$lebar}*0.18*(1-{$progres})", (string) $y],
            'naik' => [(string) $x, "{$y}+{$tinggi}*0.18*(1-{$progres})"],
            default => [(string) $x, (string) $y],
        };
    }

    /** @param array<string, mixed> $layer */
    private function buatPngTeks(array $layer, string $teks, string $path): void
    {
        $lebar = max(40, (int) $layer['lebar']);
        $tinggi = max(40, (int) $layer['tinggi']);
        $tanggal = ($layer['id'] ?? '') === 'tanggal';
        $judul = ($layer['jenis'] ?? '') === 'judul';
        $ukuran = $tanggal ? 36 : ($judul ? 68 : 42);
        $bobot = $judul ? 800 : 600;
        $jarakBaris = $judul ? 1.04 : 1.18;
        $padding = 24;
        $maksKarakter = max(8, (int) floor(($lebar - ($padding * 2)) / ($ukuran * .56)));
        $baris = $this->bungkusTeks($tanggal ? mb_strtoupper($teks) : $teks, $maksKarakter);
        $maksBaris = max(1, (int) floor(($tinggi - ($padding * 2)) / ($ukuran * $jarakBaris)));
        $baris = array_slice($baris, 0, $maksBaris);
        $tinggiTeks = count($baris) * $ukuran * $jarakBaris;
        $y = max(0, (int) (($tinggi - $tinggiTeks) / 2));
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        throw_unless($gambar, RuntimeException::class, 'Kanvas teks video tidak dapat dibuat.');
        imagesavealpha($gambar, true);
        imagefill($gambar, 0, 0, imagecolorallocatealpha($gambar, 0, 0, 0, 127));
        $warna = imagecolorallocate($gambar, 23, 42, 93);
        $font = $this->fontRoboto($bobot >= 700);
        foreach ($baris as $barisTeks) {
            imagettftext($gambar, $ukuran, 0, $padding, $y + $ukuran, $warna, $font, $barisTeks);
            $y += (int) round($ukuran * $jarakBaris);
        }
        throw_unless(imagepng($gambar, $path), RuntimeException::class, 'Teks scene video tidak dapat disimpan.');
        imagedestroy($gambar);
    }

    private function fontRoboto(bool $tebal): string
    {
        $nama = $tebal ? 'Roboto-Bold.ttf' : 'Roboto-Regular.ttf';
        $calon = [
            getenv('HOME')."/Library/Fonts/{$nama}",
            "/Library/Fonts/{$nama}",
            "/usr/share/fonts/truetype/roboto/{$nama}",
            "/usr/share/fonts/truetype/roboto/unhinted/RobotoTTF/{$nama}",
            "/usr/share/fonts/TTF/{$nama}",
            '/System/Library/Fonts/Supplemental/Arial.ttf',
            '/usr/share/fonts/truetype/liberation2/LiberationSans-Regular.ttf',
        ];
        $path = collect($calon)->first(fn (string $item) => is_file($item));
        throw_unless($path, RuntimeException::class, 'Font Roboto belum tersedia untuk render video.');

        return $path;
    }

    /** @return array<int, string> */
    private function bungkusTeks(string $teks, int $maksKarakter): array
    {
        $hasil = [];
        foreach (preg_split('/\R/u', trim($teks)) ?: [] as $paragraf) {
            $baris = '';
            foreach (preg_split('/\s+/u', trim($paragraf)) ?: [] as $kata) {
                $calon = trim($baris.' '.$kata);
                if ($baris !== '' && mb_strlen($calon) > $maksKarakter) {
                    $hasil[] = $baris;
                    $baris = $kata;
                } else {
                    $baris = $calon;
                }
            }
            if ($baris !== '') {
                $hasil[] = $baris;
            }
        }

        return $hasil ?: [''];
    }

    /** @return array{string, string, string} */
    private function ekspresiGerakan(string $gerakan, int $jumlahFrame): array
    {
        $akhir = max(1, $jumlahFrame - 1);

        return match ($gerakan) {
            'zoom_keluar' => ["max(1,1.04-0.04*on/{$akhir})", 'iw/2-(iw/zoom/2)', 'ih/2-(ih/zoom/2)'],
            'geser_kiri' => ['1.04', "(iw-iw/zoom)*on/{$akhir}", 'ih/2-(ih/zoom/2)'],
            'geser_kanan' => ['1.04', "(iw-iw/zoom)*(1-on/{$akhir})", 'ih/2-(ih/zoom/2)'],
            'diam' => ['1', '0', '0'],
            default => ["min(1.04,1+0.04*on/{$akhir})", 'iw/2-(iw/zoom/2)', 'ih/2-(ih/zoom/2)'],
        };
    }
}
