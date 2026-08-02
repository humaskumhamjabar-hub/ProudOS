<?php

namespace App\Livewire;

use App\Jobs\RenderCarousel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Content\Models\Bahan;
use Modules\Content\Models\PaketKonten;
use Modules\Visual\Models\Render;
use Modules\Visual\Models\RenderSlide;
use Modules\Visual\Models\TemplateVisual;

#[Layout('components.layouts.app')]
class StudioCarousel extends Component
{
    public ?int $paketId = null;

    public ?int $templateId = null;

    public ?int $renderAktifId = null;

    public ?int $slideAktifId = null;

    public float $posisiX = 0;

    public float $posisiY = 0;

    public float $zoom = 1;

    public string $kickerSlide = '';

    public string $judulSlide = '';

    public string $isiSlide = '';

    public function mount(?int $paket = null): void
    {
        Gate::authorize('kelola_konten');
        $this->paketId = $paket
            ? PaketKonten::findOrFail($paket)->id
            : PaketKonten::where('status', '!=', 'arsip')->latest('updated_at')->value('id');
        $this->templateId = TemplateVisual::where('status', 'aktif')->where('format', 'ig_carousel')->orderByDesc('versi')->orderByDesc('id')->value('id');

        if ($this->paketId) {
            $render = Render::where('paket_konten_id', $this->paketId)->latest()->first();

            if ($render) {
                $this->pilihRender($render->id);
            }
        }
    }

    public function pilihPaket(int $paketId): void
    {
        Gate::authorize('kelola_konten');
        $this->paketId = PaketKonten::findOrFail($paketId)->id;
        $this->reset(['renderAktifId', 'slideAktifId']);
        $render = Render::where('paket_konten_id', $this->paketId)->latest()->first();

        if ($render) {
            $this->pilihRender($render->id);
        }
    }

    public function siapkanCarousel(): void
    {
        Gate::authorize('kelola_konten');
        $paket = PaketKonten::findOrFail($this->paketId);
        $template = TemplateVisual::with('layouts')
            ->where('status', 'aktif')
            ->where('format', 'ig_carousel')
            ->find($this->templateId);
        $foto = $paket->bahan()
            ->where('tipe', 'foto')
            ->where('dipakai_final', true)
            ->orderBy('urutan')
            ->get();

        if (! $template) {
            $this->addError('carousel', 'Pilih template carousel yang aktif.');

            return;
        }

        if (! $template->layouts->contains('jenis', 'cover') || ! $template->layouts->contains('jenis', 'isi')) {
            $this->addError('carousel', 'Template belum memiliki layout cover dan isi.');

            return;
        }

        if ($foto->isEmpty()) {
            $this->addError('carousel', 'Tandai minimal satu foto sebagai bahan final sebelum membuat carousel.');

            return;
        }

        $naskah = $paket->draf()->latest('versi')->value('isi') ?? '';
        $potongan = collect(preg_split('/\n{2,}|(?<=[.!?])\s+/u', trim($naskah)) ?: [])
            ->map(fn (string $teks) => trim($teks))
            ->filter()
            ->values();

        $render = DB::transaction(function () use ($paket, $template, $foto, $potongan) {
            $render = Render::create([
                'paket_konten_id' => $paket->id,
                'template_visual_id' => $template->id,
                'template_versi' => $template->versi,
                'format' => $template->format,
                'status' => 'antre',
            ]);

            foreach ($foto as $index => $bahan) {
                $jenis = $index === 0 ? 'cover' : 'isi';
                $render->slides()->create([
                    'urutan' => $index + 1,
                    'jenis' => $jenis,
                    'bahan_id' => $bahan->id,
                    'posisi_foto' => ['x' => 0, 'y' => 0, 'zoom' => 1],
                    'isi_teks' => $jenis === 'cover'
                        ? ['kicker' => $paket->subjudul ?: 'KEMENKUM JAWA BARAT', 'judul' => $paket->judul, 'isi' => '']
                        : ['kicker' => 'SOROTAN '.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'judul' => $potongan->get(($index - 1) * 2, 'Informasi utama kegiatan'), 'isi' => $potongan->get((($index - 1) * 2) + 1, '')],
                ]);
            }

            return $render;
        });

        RenderCarousel::dispatch($render->id);
        $this->pilihRender($render->id);
        $this->resetValidation();
        session()->flash('visual-tersimpan', 'Carousel disiapkan. Atur crop dan teks sambil render awal berjalan.');
    }

    public function pilihRender(int $renderId): void
    {
        Gate::authorize('kelola_konten');
        $render = Render::where('paket_konten_id', $this->paketId)->findOrFail($renderId);
        $this->renderAktifId = $render->id;
        $slide = $render->slides()->first();

        if ($slide) {
            $this->pilihSlide($slide->id);
        }
    }

    public function pilihSlide(int $slideId): void
    {
        Gate::authorize('kelola_konten');
        abort_unless($this->renderAktifId, 404);
        $slide = $this->slideMilikRenderAktif($slideId);
        $this->slideAktifId = $slide->id;
        $this->posisiX = (float) ($slide->posisi_foto['x'] ?? 0);
        $this->posisiY = (float) ($slide->posisi_foto['y'] ?? 0);
        $this->zoom = (float) ($slide->posisi_foto['zoom'] ?? 1);
        $this->kickerSlide = (string) ($slide->isi_teks['kicker'] ?? '');
        $this->judulSlide = (string) ($slide->isi_teks['judul'] ?? '');
        $this->isiSlide = (string) ($slide->isi_teks['isi'] ?? '');
        $this->resetValidation();
    }

    public function simpanSlide(): void
    {
        Gate::authorize('kelola_konten');
        $slide = $this->slideMilikRenderAktif((int) $this->slideAktifId);
        $template = TemplateVisual::with('layouts')->findOrFail(Render::findOrFail($this->renderAktifId)->template_visual_id);
        $batas = $template->layouts->firstWhere('jenis', $slide->jenis)?->batas_karakter ?? [];
        $data = $this->validate([
            'posisiX' => ['required', 'numeric', 'between:-100,100'],
            'posisiY' => ['required', 'numeric', 'between:-100,100'],
            'zoom' => ['required', 'numeric', 'between:1,3'],
            'kickerSlide' => ['nullable', 'string', 'max:'.($batas['kicker'] ?? 40)],
            'judulSlide' => ['required', 'string', 'max:'.($batas['judul'] ?? 90)],
            'isiSlide' => ['nullable', 'string', 'max:'.($batas['isi'] ?? 280)],
        ], [], [
            'posisiX' => 'posisi horizontal',
            'posisiY' => 'posisi vertikal',
            'kickerSlide' => 'label slide',
            'judulSlide' => 'judul slide',
            'isiSlide' => 'isi slide',
        ]);

        $slide->update([
            'posisi_foto' => ['x' => (float) $data['posisiX'], 'y' => (float) $data['posisiY'], 'zoom' => (float) $data['zoom']],
            'isi_teks' => ['kicker' => $data['kickerSlide'] ?? '', 'judul' => $data['judulSlide'], 'isi' => $data['isiSlide'] ?? ''],
        ]);
        Render::findOrFail($this->renderAktifId)->update(['status' => 'antre', 'path_hasil' => null, 'pesan_gagal' => null]);
        session()->flash('visual-tersimpan', 'Komposisi slide tersimpan. Render ulang saat semua slide siap.');
    }

    public function renderUlang(): void
    {
        Gate::authorize('kelola_konten');
        $render = Render::where('paket_konten_id', $this->paketId)->findOrFail($this->renderAktifId);
        $render->update(['status' => 'antre', 'pesan_gagal' => null]);
        RenderCarousel::dispatch($render->id);
        session()->flash('visual-tersimpan', 'Render ulang masuk antrean.');
    }

    public function unduhHasil(int $renderId)
    {
        Gate::authorize('kelola_konten');
        $render = Render::where('paket_konten_id', $this->paketId)->where('status', 'selesai')->findOrFail($renderId);
        abort_unless($render->path_hasil && Storage::disk('local')->exists($render->path_hasil), 404);

        return Storage::disk('local')->download($render->path_hasil, "carousel-{$render->id}.zip");
    }

    private function slideMilikRenderAktif(int $slideId): RenderSlide
    {
        $render = Render::where('paket_konten_id', $this->paketId)->findOrFail($this->renderAktifId);

        return $render->slides()->findOrFail($slideId);
    }

    public function render()
    {
        $paket = PaketKonten::where('status', '!=', 'arsip')->latest('updated_at')->get();
        $paketAktif = $this->paketId ? $paket->firstWhere('id', $this->paketId) : null;
        $template = TemplateVisual::with('layouts')->where('format', 'ig_carousel')->orderByDesc('status')->orderByDesc('versi')->get();
        $riwayatRender = $this->paketId ? Render::with('slides')->where('paket_konten_id', $this->paketId)->latest()->get() : collect();
        $renderAktif = $this->renderAktifId ? $riwayatRender->firstWhere('id', $this->renderAktifId) : null;
        $slideAktif = $renderAktif?->slides->firstWhere('id', $this->slideAktifId);
        $bahanAktif = $slideAktif?->bahan_id ? Bahan::find($slideAktif->bahan_id) : null;
        $fotoPreview = null;

        if ($bahanAktif?->path && Storage::disk('local')->exists($bahanAktif->path)) {
            $fotoPreview = 'data:'.($bahanAktif->mime ?: 'image/jpeg').';base64,'.base64_encode(Storage::disk('local')->get($bahanAktif->path));
        }

        return view('livewire.studio-carousel', compact(
            'paket', 'paketAktif', 'template', 'riwayatRender', 'renderAktif', 'slideAktif', 'fotoPreview',
        ));
    }
}
