<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Work\Models\Tugas;
use Modules\Work\Models\TugasCatatanLapangan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LihatHasilCarouselTugasController
{
    public function __invoke(Tugas $tugas, int $urutan): BinaryFileResponse
    {
        Gate::authorize('lihat-tugas', $tugas);
        abort_unless(in_array($urutan, [1, 2, 3], true), 404);

        $catatan = TugasCatatanLapangan::query()
            ->where('tugas_id', $tugas->id)
            ->where('dibuat_oleh', Auth::id())
            ->firstOrFail();
        $slide = collect($catatan->carousel_sosmed_slides ?? [])
            ->first(fn ($item) => is_array($item) && (int) ($item['urutan'] ?? 0) === $urutan);
        $path = is_array($slide) ? ($slide['path'] ?? null) : null;

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'image/png',
            'Content-Disposition' => "inline; filename=carousel-{$urutan}.png",
        ]);
    }
}
