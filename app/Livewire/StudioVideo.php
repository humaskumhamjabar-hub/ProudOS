<?php

namespace App\Livewire;

use App\Jobs\RenderVideo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Content\Models\PaketKonten;
use Modules\Visual\Models\Render;
use Modules\Visual\Models\TemplateVisual;

#[Layout('components.layouts.app')]
class StudioVideo extends Component
{
    public ?int $paketId = null;

    public ?int $templateId = null;

    public function mount(?int $paket = null): void
    {
        Gate::authorize('kelola_konten');
        $this->paketId = $paket
            ? PaketKonten::whereKey($paket)->where('status', '!=', 'arsip')->valueOrFail('id')
            : PaketKonten::where('status', '!=', 'arsip')->latest('updated_at')->value('id');
        $this->templateId = TemplateVisual::where('status', 'aktif')->where('format', 'video_vertikal')->orderByDesc('versi')->orderByDesc('id')->value('id');
    }

    public function buatVideo(): void
    {
        Gate::authorize('kelola_konten');
        $paket = PaketKonten::whereKey($this->paketId)->where('status', '!=', 'arsip')->firstOrFail();
        $template = TemplateVisual::where('status', 'aktif')->where('format', 'video_vertikal')->find($this->templateId);
        $foto = $paket->bahan()->where('tipe', 'foto')->where('dipakai_final', true)->orderBy('urutan')->get();

        if (! $template) {
            $this->addError('video', 'Pilih template video vertikal yang aktif.');

            return;
        }
        if ($foto->isEmpty()) {
            $this->addError('video', 'Tandai minimal satu foto sebagai bahan final.');

            return;
        }

        $render = DB::transaction(function () use ($paket, $template, $foto) {
            $render = Render::create([
                'paket_konten_id' => $paket->id,
                'template_visual_id' => $template->id,
                'template_versi' => $template->versi,
                'format' => $template->format,
                'status' => 'antre',
            ]);
            foreach ($foto as $index => $bahan) {
                $render->slides()->create([
                    'urutan' => $index + 1,
                    'jenis' => $index === 0 ? 'cover' : 'isi',
                    'bahan_id' => $bahan->id,
                    'posisi_foto' => ['x' => 0, 'y' => 0, 'zoom' => 1],
                    'isi_teks' => ['kicker' => $paket->subjudul ?? '', 'judul' => $paket->judul, 'isi' => ''],
                ]);
            }

            return $render;
        });

        RenderVideo::dispatch($render->id);
        session()->flash('video-sukses', 'Video vertikal masuk antrean render.');
    }

    public function unduh(int $renderId)
    {
        Gate::authorize('kelola_konten');
        $render = Render::where('paket_konten_id', $this->paketId)->where('format', 'video_vertikal')->where('status', 'selesai')->findOrFail($renderId);
        abort_unless($render->path_hasil && Storage::disk('local')->exists($render->path_hasil), 404);

        return Storage::disk('local')->download($render->path_hasil, "video-{$render->id}.mp4");
    }

    public function render()
    {
        return view('livewire.studio-video', [
            'paket' => PaketKonten::where('status', '!=', 'arsip')->latest('updated_at')->get(),
            'templates' => TemplateVisual::where('format', 'video_vertikal')->orderByDesc('status')->orderByDesc('versi')->get(),
            'riwayat' => $this->paketId ? Render::where('paket_konten_id', $this->paketId)->where('format', 'video_vertikal')->latest()->get() : collect(),
        ]);
    }
}
