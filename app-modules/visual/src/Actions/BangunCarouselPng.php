<?php

namespace Modules\Visual\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Visual\Contracts\PenangkapHtml;
use Modules\Visual\Models\Render;
use RuntimeException;
use ZipArchive;

class BangunCarouselPng
{
    public function __construct(private readonly PenangkapHtml $penangkap) {}

    /** @param array<int, array{mime: string, data: string}> $gambar */
    public function handle(Render $render, array $gambar): string
    {
        $render->loadMissing('slides');
        $direktori = Storage::disk('local')->path("render/{$render->id}");

        if (! is_dir($direktori) && ! mkdir($direktori, 0755, true) && ! is_dir($direktori)) {
            throw new RuntimeException('Direktori render tidak dapat dibuat.');
        }

        foreach ($render->slides as $slide) {
            $html = view('visual::carousel-slide', [
                'slide' => $slide,
                'gambar' => isset($gambar[$slide->id])
                    ? 'data:'.$gambar[$slide->id]['mime'].';base64,'.base64_encode($gambar[$slide->id]['data'])
                    : null,
            ])->render();
            $htmlPath = "{$direktori}/slide-{$slide->urutan}.html";
            $pngPath = "{$direktori}/slide-{$slide->urutan}.png";
            file_put_contents($htmlPath, $html);

            $this->penangkap->tangkap($htmlPath, $pngPath, 1080, 1350);

            if (! is_file($pngPath)) {
                throw new RuntimeException("Slide {$slide->urutan} gagal dibuat.");
            }
        }

        $zipPath = "{$direktori}/carousel-{$render->id}.zip";
        $zip = new ZipArchive;
        throw_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, RuntimeException::class, 'ZIP hasil tidak dapat dibuat.');

        foreach ($render->slides as $slide) {
            $zip->addFile("{$direktori}/slide-{$slide->urutan}.png", 'slide-'.str_pad((string) $slide->urutan, 2, '0', STR_PAD_LEFT).'.png');
        }

        $zip->close();

        return "render/{$render->id}/carousel-{$render->id}.zip";
    }
}
