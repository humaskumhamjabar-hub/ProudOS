<?php

namespace App\Actions;

use App\Support\PenempatanCarousel;
use Illuminate\Support\Facades\Storage;
use Modules\Visual\Contracts\PenangkapHtml;
use Modules\Visual\Models\TemplateVisual;
use Modules\Work\Models\TugasBahan;
use RuntimeException;

class SimpanCarouselSosmed
{
    public function __construct(private readonly PenangkapHtml $penangkap) {}

    /**
     * @param  array<int, TugasBahan>  $bahanFoto
     * @param  array<string, mixed>  $slide
     * @return array{path: string, mime: string}
     */
    public function handle(array $bahanFoto, int $tugasId, int $userId, array $slide, ?string $backgroundPath = null, ?TemplateVisual $template = null): array
    {
        throw_if($bahanFoto === [], RuntimeException::class, 'Foto slide carousel belum dipilih.');

        $disk = Storage::disk('local');
        $foto = [];
        foreach ($bahanFoto as $index => $bahan) {
            abort_unless($bahan->tugas_id === $tugasId && str_starts_with($bahan->mime, 'image/'), 422);
            abort_unless($disk->exists($bahan->path), 404);
            $foto[$index] = 'data:'.$bahan->mime.';base64,'.base64_encode($disk->get($bahan->path));
        }

        $direktori = "tugas-sosmed/{$tugasId}/{$userId}";
        $disk->makeDirectory($direktori);
        $nomor = str_pad((string) $slide['urutan'], 2, '0', STR_PAD_LEFT);
        $htmlPath = $disk->path("{$direktori}/carousel-{$nomor}.html");
        $pngPath = $disk->path("{$direktori}/carousel-{$nomor}.png");

        $background = $backgroundPath && $disk->exists($backgroundPath)
            ? 'data:image/png;base64,'.base64_encode($disk->get($backgroundPath))
            : null;
        $penempatan = PenempatanCarousel::untukTemplate($template, (int) $slide['urutan'] - 1);
        $html = view('carousel-sosmed.slide', compact('slide', 'foto', 'background', 'penempatan'))->render();
        throw_if(file_put_contents($htmlPath, $html) === false, RuntimeException::class, 'Template carousel tidak dapat disiapkan.');
        $this->penangkap->tangkap($htmlPath, $pngPath, 1080, 1350);
        throw_unless(is_file($pngPath) && filesize($pngPath) > 0, RuntimeException::class, 'Slide carousel tidak dapat disimpan.');

        return ['path' => "{$direktori}/carousel-{$nomor}.png", 'mime' => 'image/png'];
    }
}
