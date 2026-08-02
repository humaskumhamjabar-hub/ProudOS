<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Work\Models\Tugas;
use Modules\Work\Models\TugasBahan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LihatFotoTugasController
{
    public function __invoke(Tugas $tugas, TugasBahan $bahan): BinaryFileResponse
    {
        Gate::authorize('lihat-tugas', $tugas);
        abort_unless($bahan->tugas_id === $tugas->id && str_starts_with($bahan->mime, 'image/'), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($bahan->path), 404);

        return response()->file($disk->path($bahan->path), [
            'Content-Type' => $bahan->mime,
            'Content-Disposition' => 'inline; filename="'.basename($bahan->nama_asli).'"',
        ]);
    }
}
