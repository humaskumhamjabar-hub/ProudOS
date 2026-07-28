<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Modules\Content\Models\Bahan;
use Modules\Visual\Actions\BangunVideoVertikal;
use Modules\Visual\Models\Render;

class RenderVideo implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 360;

    public function __construct(public int $renderId) {}

    public function handle(BangunVideoVertikal $pembangun): void
    {
        $render = Render::with(['slides', 'template'])->find($this->renderId);
        if (! $render) {
            return;
        }

        $render->update(['status' => 'proses', 'dikerjakan_at' => now(), 'pesan_gagal' => null]);

        try {
            $bahan = Bahan::whereIn('id', $render->slides->pluck('bahan_id')->filter())->get()->keyBy('id');
            $gambar = $render->slides->mapWithKeys(function ($slide) use ($bahan) {
                $item = $bahan->get($slide->bahan_id);
                if (! $item || ! Storage::disk('local')->exists($item->path)) {
                    return [];
                }

                return [$slide->id => [
                    'mime' => $item->mime ?: 'image/jpeg',
                    'path' => Storage::disk('local')->path($item->path),
                ]];
            })->all();

            $path = $pembangun->handle($render, $gambar);
            $render->update(['status' => 'selesai', 'path_hasil' => $path, 'selesai_at' => now()]);
        } catch (\Throwable $exception) {
            $render->update(['status' => 'gagal', 'pesan_gagal' => str($exception->getMessage())->limit(1000)]);
            throw $exception;
        }
    }
}
