<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Modules\Visual\Models\TemplateAset;
use Modules\Visual\Models\TemplateVisual;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LihatAsetTemplateVisualController
{
    public function __invoke(TemplateVisual $template, TemplateAset $aset): BinaryFileResponse
    {
        abort_unless(
            $aset->template_visual_id === $template->id
            && (str_starts_with($aset->jenis, 'background_slide_') || str_starts_with($aset->jenis, 'video_scene_')),
            404,
        );
        $disk = Storage::disk('local');
        abort_unless($disk->exists($aset->path), 404);

        return response()->file($disk->path($aset->path), [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="'.basename($aset->path).'"',
        ]);
    }
}
