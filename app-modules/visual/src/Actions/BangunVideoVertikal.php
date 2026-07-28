<?php

namespace Modules\Visual\Actions;

use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Support\Facades\Storage;
use Modules\Visual\Models\Render;
use RuntimeException;

class BangunVideoVertikal
{
    public function __construct(private readonly ProcessFactory $process) {}

    /** @param array<int, array{mime: string, path: string}> $gambar */
    public function handle(Render $render, array $gambar): string
    {
        $render->loadMissing('slides');

        if ($render->slides->isEmpty()) {
            throw new RuntimeException('Video membutuhkan minimal satu slide.');
        }

        $ffmpeg = (string) config('visual.ffmpeg_path');
        if ($ffmpeg === '' || ! is_executable($ffmpeg)) {
            throw new RuntimeException('FFmpeg belum tersedia di server.');
        }

        $durasi = max(1, (int) ($render->template?->durasi_per_slide_detik ?? 4));
        $direktori = Storage::disk('local')->path("render/{$render->id}");
        if (! is_dir($direktori) && ! mkdir($direktori, 0755, true) && ! is_dir($direktori)) {
            throw new RuntimeException('Direktori render video tidak dapat dibuat.');
        }

        $concat = [];
        foreach ($render->slides as $slide) {
            if (! isset($gambar[$slide->id])) {
                throw new RuntimeException("Foto untuk slide {$slide->urutan} tidak tersedia.");
            }

            $path = $gambar[$slide->id]['path'];
            $concat[] = "file '".str_replace("'", "'\\\\''", $path)."'";
            $concat[] = "duration {$durasi}";
        }
        $concat[] = "file '".str_replace("'", "'\\\\''", $path)."'";

        $daftar = "{$direktori}/video-input.txt";
        file_put_contents($daftar, implode("\n", $concat)."\n");
        $hasil = "{$direktori}/video-{$render->id}.mp4";

        $this->process->timeout(300)->run([
            $ffmpeg, '-y', '-f', 'concat', '-safe', '0', '-i', $daftar,
            '-vf', 'scale=1080:1920:force_original_aspect_ratio=increase,crop=1080:1920,format=yuv420p',
            '-r', '30', '-an', '-c:v', 'libx264', '-preset', 'medium', '-movflags', '+faststart', $hasil,
        ])->throw();

        if (! is_file($hasil) || filesize($hasil) === 0) {
            throw new RuntimeException('FFmpeg tidak menghasilkan video.');
        }

        return "render/{$render->id}/video-{$render->id}.mp4";
    }
}
