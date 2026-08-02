<?php

namespace App\Livewire;

use App\Support\PenempatanCarousel;
use App\Support\PenempatanVideoTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Visual\Models\Render;
use Modules\Visual\Models\TemplateVisual;
use Modules\Work\Models\TugasCatatanLapangan;

#[Layout('components.layouts.app')]
class KelolaTemplateVisual extends Component
{
    use WithFileUploads;

    public ?int $templateId = null;

    public string $nama = '';

    public string $format = 'ig_carousel';

    public string $rasio = '4:5';

    public ?int $durasiPerSlide = null;

    public int $coverKicker = 32;

    public int $coverJudul = 90;

    public int $coverIsi = 0;

    public int $isiKicker = 32;

    public int $isiJudul = 72;

    public int $isiTeks = 280;

    public array $backgroundSlides = [];

    public array $penempatanSlides = [];

    public int $slidePenempatanAktif = 0;

    public int $slotPenempatanAktif = 0;

    public array $videoScenes = [];

    public int $videoSceneAktif = 0;

    public int $videoLayerAktif = 0;

    public array $videoLayerUploads = [];

    public string $jenisLayerBaru = 'png';

    public ?int $templateHapusId = null;

    public function mount(): void
    {
        Gate::authorize('kelola_template_visual');
        $template = TemplateVisual::with(['layouts', 'aset'])
            ->orderByDesc('updated_at')
            ->orderByDesc('versi')
            ->orderByDesc('id')
            ->first();

        if ($template) {
            $this->pilihTemplate($template->id);

            return;
        }

        $this->penempatanSlides = PenempatanCarousel::bawaan();
        $this->videoScenes = PenempatanVideoTemplate::bawaan();
    }

    public function pilihTemplate(int $templateId): void
    {
        Gate::authorize('kelola_template_visual');
        $template = TemplateVisual::with(['layouts', 'aset'])->findOrFail($templateId);
        $cover = $template->layouts->firstWhere('jenis', 'cover');
        $isi = $template->layouts->firstWhere('jenis', 'isi');

        $this->templateId = $template->id;
        $this->nama = $template->nama;
        $this->format = $template->format;
        $this->rasio = $template->rasio;
        $this->durasiPerSlide = $template->durasi_per_slide_detik;
        $this->coverKicker = (int) ($cover?->batas_karakter['kicker'] ?? 32);
        $this->coverJudul = (int) ($cover?->batas_karakter['judul'] ?? 90);
        $this->coverIsi = (int) ($cover?->batas_karakter['isi'] ?? 0);
        $this->isiKicker = (int) ($isi?->batas_karakter['kicker'] ?? 32);
        $this->isiJudul = (int) ($isi?->batas_karakter['judul'] ?? 72);
        $this->isiTeks = (int) ($isi?->batas_karakter['isi'] ?? 280);
        $this->backgroundSlides = [];
        $this->penempatanSlides = array_map(
            fn (int $index) => PenempatanCarousel::untukTemplate($template, $index),
            [0, 1, 2],
        );
        $this->slidePenempatanAktif = 0;
        $this->slotPenempatanAktif = 0;
        $this->videoScenes = array_map(
            fn (int $index) => PenempatanVideoTemplate::untukTemplate($template, $index),
            [0, 1, 2],
        );
        $this->videoLayerUploads = [];
        $this->videoSceneAktif = 0;
        $this->videoLayerAktif = 0;
        $this->resetValidation();
    }

    public function buatTemplateBaru(): void
    {
        Gate::authorize('kelola_template_visual');
        $this->reset([
            'templateId', 'nama', 'durasiPerSlide',
        ]);
        $this->format = 'ig_carousel';
        $this->rasio = '4:5';
        $this->coverKicker = 32;
        $this->coverJudul = 90;
        $this->coverIsi = 0;
        $this->isiKicker = 32;
        $this->isiJudul = 72;
        $this->isiTeks = 280;
        $this->backgroundSlides = [];
        $this->penempatanSlides = PenempatanCarousel::bawaan();
        $this->slidePenempatanAktif = 0;
        $this->slotPenempatanAktif = 0;
        $this->videoScenes = PenempatanVideoTemplate::bawaan();
        $this->videoLayerUploads = [];
        $this->videoSceneAktif = 0;
        $this->videoLayerAktif = 0;
        $this->resetValidation();
    }

    public function mintaHapusTemplate(int $templateId): void
    {
        Gate::authorize('kelola_template_visual');
        TemplateVisual::findOrFail($templateId);
        $this->templateHapusId = $templateId;
        $this->resetValidation('template');
    }

    public function batalHapusTemplate(): void
    {
        $this->templateHapusId = null;
    }

    public function hapusTemplate(int $templateId): void
    {
        Gate::authorize('kelola_template_visual');
        abort_unless($this->templateHapusId === $templateId, 422);
        $template = TemplateVisual::with('aset')->findOrFail($templateId);

        if (Render::where('template_visual_id', $template->id)->exists()) {
            $this->addError('template', 'Template sudah dipakai dalam riwayat render dan tidak dapat dihapus. Buat versi baru atau biarkan sebagai arsip.');
            $this->templateHapusId = null;

            return;
        }

        $paths = $template->aset->pluck('path')->filter()->values()->all();
        DB::transaction(function () use ($template) {
            TugasCatatanLapangan::query()
                ->where('carousel_sosmed_template_id', $template->id)
                ->update(['carousel_sosmed_template_id' => null, 'carousel_sosmed_template_versi' => null]);
            TugasCatatanLapangan::query()
                ->where('video_sosmed_template_id', $template->id)
                ->update(['video_sosmed_template_id' => null, 'video_sosmed_template_versi' => null]);
            $template->delete();
        });
        Storage::disk('local')->delete($paths);
        Storage::disk('local')->deleteDirectory("template-visual/{$template->id}");

        $this->templateHapusId = null;
        $berikutnya = TemplateVisual::with(['layouts', 'aset'])
            ->orderByDesc('updated_at')
            ->orderByDesc('versi')
            ->orderByDesc('id')
            ->first();
        if ($berikutnya) {
            $this->pilihTemplate($berikutnya->id);
        } else {
            $this->buatTemplateBaru();
        }
        session()->flash('template-tersimpan', "Template {$template->nama} v{$template->versi} berhasil dihapus.");
    }

    public function updatedFormat(string $format): void
    {
        if ($format === 'video_vertikal') {
            $this->rasio = '9:16';
            $this->durasiPerSlide ??= 8;
            $this->videoScenes = $this->videoScenes ?: PenempatanVideoTemplate::bawaan();
        } elseif ($format === 'ig_carousel') {
            $this->rasio = '4:5';
        }
    }

    public function pilihVideoScene(int $index): void
    {
        abort_unless(isset($this->videoScenes[$index]), 404);
        $this->videoSceneAktif = $index;
        $this->videoLayerAktif = 0;
    }

    public function pilihVideoLayer(int $index): void
    {
        abort_unless(isset($this->videoScenes[$this->videoSceneAktif]['layers'][$index]), 404);
        $this->videoLayerAktif = $index;
    }

    public function tambahVideoLayer(): void
    {
        abort_unless(in_array($this->jenisLayerBaru, ['png', 'foto', 'judul', 'paragraf'], true), 422);
        $layers = &$this->videoScenes[$this->videoSceneAktif]['layers'];
        if (count($layers) >= 20) {
            $this->addError('videoScenes', 'Maksimal 20 layer dalam satu scene.');

            return;
        }

        $layers[] = PenempatanVideoTemplate::layerBaru($this->jenisLayerBaru, count($layers));
        $this->videoLayerAktif = count($layers) - 1;
    }

    public function hapusVideoLayer(int $index): void
    {
        abort_unless(isset($this->videoScenes[$this->videoSceneAktif]['layers'][$index]), 404);
        $layer = $this->videoScenes[$this->videoSceneAktif]['layers'][$index];
        abort_if(in_array($layer['id'] ?? '', ['background', 'foto', 'judul', 'paragraf_1'], true), 422);
        array_splice($this->videoScenes[$this->videoSceneAktif]['layers'], $index, 1);
        if (isset($this->videoLayerUploads[$this->videoSceneAktif])) {
            array_splice($this->videoLayerUploads[$this->videoSceneAktif], $index, 1);
        }
        $this->videoLayerAktif = max(0, min($this->videoLayerAktif, count($this->videoScenes[$this->videoSceneAktif]['layers']) - 1));
    }

    public function resetVideoScene(): void
    {
        $this->videoScenes[$this->videoSceneAktif] = PenempatanVideoTemplate::bawaan()[$this->videoSceneAktif];
        $this->videoLayerAktif = 0;
        unset($this->videoLayerUploads[$this->videoSceneAktif]);
    }

    public function pilihSlidePenempatan(int $index): void
    {
        abort_unless(in_array($index, [0, 1, 2], true), 404);
        $this->slidePenempatanAktif = $index;
        $this->slotPenempatanAktif = 0;
    }

    public function pilihSlotPenempatan(int $slot): void
    {
        $jumlahSlot = $this->slidePenempatanAktif === 0 ? 3 : 1;
        abort_unless($slot >= 0 && $slot < $jumlahSlot, 404);
        $this->slotPenempatanAktif = $slot;
    }

    public function resetPenempatanSlide(): void
    {
        $this->penempatanSlides[$this->slidePenempatanAktif] = PenempatanCarousel::bawaan()[$this->slidePenempatanAktif];
        $this->slotPenempatanAktif = 0;
    }

    public function buatVersiBaru(int $templateId): void
    {
        Gate::authorize('kelola_template_visual');
        $asal = TemplateVisual::with(['layouts', 'aset'])->findOrFail($templateId);

        $baru = DB::transaction(function () use ($asal) {
            $versi = $this->versiBerikutnya($asal->nama);
            $baru = TemplateVisual::create([
                'nama' => $asal->nama,
                'format' => $asal->format,
                'rasio' => $asal->rasio,
                'versi' => $versi,
                'status' => 'draf',
                'durasi_per_slide_detik' => $asal->durasi_per_slide_detik,
                'dibuat_oleh' => Auth::id(),
            ]);

            foreach ($asal->layouts as $layout) {
                $baru->layouts()->create([
                    'jenis' => $layout->jenis,
                    'definisi' => $layout->definisi,
                    'batas_karakter' => $layout->batas_karakter,
                ]);
            }
            foreach ($asal->aset as $aset) {
                if ((! str_starts_with($aset->jenis, 'background_slide_') && ! str_starts_with($aset->jenis, 'video_scene_')) || ! Storage::disk('local')->exists($aset->path)) {
                    continue;
                }
                $path = "template-visual/{$baru->id}/".basename($aset->path);
                Storage::disk('local')->copy($aset->path, $path);
                $baru->aset()->create(['jenis' => $aset->jenis, 'path' => $path]);
            }

            return $baru;
        });

        $this->pilihTemplate($baru->id);
        session()->flash('template-tersimpan', "Versi {$baru->versi} dibuat sebagai draf. Tinjau preview sebelum mengaktifkan.");
    }

    public function simpanDraf(): void
    {
        $this->simpan(false);
    }

    public function simpanDanAktifkan(): void
    {
        $template = $this->simpan(false);

        $this->aktifkan($template->id);
    }

    public function simpanBackgroundCarousel(): void
    {
        $this->simpan(true);
    }

    private function simpan(bool $backgroundHarusLengkap): TemplateVisual
    {
        Gate::authorize('kelola_template_visual');
        $template = $this->templateId ? TemplateVisual::findOrFail($this->templateId) : null;

        if ($template?->status === 'aktif') {
            throw ValidationException::withMessages([
                'template' => 'Template aktif tidak diubah langsung. Buat versi baru terlebih dahulu.',
            ]);
        }

        $data = $this->validate($this->aturanValidasi());
        if ($data['format'] === 'ig_carousel') {
            $this->pastikanPenempatanDiDalamKanvas($data['penempatanSlides']);
        }
        if ($data['format'] === 'video_vertikal') {
            $this->pastikanVideoDiDalamKanvas($data['videoScenes']);
        }
        $backgroundBaru = collect($this->backgroundSlides)
            ->filter()
            ->all();

        if ($backgroundHarusLengkap) {
            $jenisTersimpan = $template?->aset()
                ->whereIn('jenis', ['background_slide_1', 'background_slide_2', 'background_slide_3'])
                ->pluck('jenis')
                ->all() ?? [];
            $jenisSetelahDisimpan = collect($jenisTersimpan)
                ->merge(array_map(fn ($index) => 'background_slide_'.($index + 1), array_keys($backgroundBaru)))
                ->unique();

            if ($jenisSetelahDisimpan->count() !== 3) {
                throw ValidationException::withMessages([
                    'backgroundSlides' => 'Lengkapi tiga background sebelum menyimpan paket carousel.',
                ]);
            }
        }

        $template = DB::transaction(function () use ($template, $data) {
            if (! $template) {
                $versi = $this->versiBerikutnya($data['nama']);
                $template = TemplateVisual::create([
                    'nama' => $data['nama'],
                    'format' => $data['format'],
                    'rasio' => $data['rasio'],
                    'versi' => $versi,
                    'status' => 'draf',
                    'durasi_per_slide_detik' => $data['durasiPerSlide'] ?? null,
                    'dibuat_oleh' => Auth::id(),
                ]);
            } else {
                $template->update([
                    'nama' => $data['nama'],
                    'format' => $data['format'],
                    'rasio' => $data['rasio'],
                    'durasi_per_slide_detik' => $data['durasiPerSlide'] ?? null,
                ]);
            }

            foreach (['cover', 'isi'] as $jenis) {
                $template->layouts()->updateOrCreate(
                    ['jenis' => $jenis],
                    [
                        'definisi' => ['tema' => str($data['nama'])->slug()->value()],
                        'batas_karakter' => $jenis === 'cover'
                            ? ['kicker' => $data['coverKicker'], 'judul' => $data['coverJudul'], 'isi' => $data['coverIsi']]
                            : ['kicker' => $data['isiKicker'], 'judul' => $data['isiJudul'], 'isi' => $data['isiTeks']],
                    ],
                );
            }

            foreach ($data['penempatanSlides'] as $index => $penempatan) {
                $template->layouts()->updateOrCreate(
                    ['jenis' => 'carousel_slide_'.($index + 1)],
                    [
                        'definisi' => PenempatanCarousel::normalisasi($penempatan, $index),
                        'batas_karakter' => [],
                    ],
                );
            }

            if ($data['format'] === 'video_vertikal') {
                foreach ($data['videoScenes'] as $index => $scene) {
                    $template->layouts()->updateOrCreate(
                        ['jenis' => 'video_scene_'.($index + 1)],
                        ['definisi' => PenempatanVideoTemplate::normalisasi($scene, $index), 'batas_karakter' => []],
                    );
                }
            }

            return $template;
        });

        $jumlahBackgroundDisimpan = $this->simpanFileBackground($template, $backgroundBaru);
        $jumlahLayerVideoDisimpan = $data['format'] === 'video_vertikal'
            ? $this->simpanFileLayerVideo($template, $this->videoLayerUploads)
            : 0;

        $this->pilihTemplate($template->id);
        session()->flash(
            'template-tersimpan',
            $backgroundHarusLengkap
                ? '3 background berhasil disimpan ke template.'
                : ($jumlahBackgroundDisimpan > 0
                    ? "Draf dan {$jumlahBackgroundDisimpan} background berhasil disimpan."
                    : ($jumlahLayerVideoDisimpan > 0
                        ? "Draf dan {$jumlahLayerVideoDisimpan} aset video berhasil disimpan."
                        : 'Draf template tersimpan. Periksa preview sebelum mengaktifkan.')),
        );

        return $template;
    }

    /** @param array<int, mixed> $backgroundBaru */
    private function simpanFileBackground(TemplateVisual $template, array $backgroundBaru): int
    {
        if ($backgroundBaru === []) {
            return 0;
        }

        $fileBaru = [];
        $fileLama = [];

        try {
            foreach ($backgroundBaru as $index => $file) {
                $jenis = 'background_slide_'.($index + 1);
                $path = $file->store("template-visual/{$template->id}", 'local');

                if (! $path || ! Storage::disk('local')->exists($path)) {
                    throw ValidationException::withMessages([
                        "backgroundSlides.{$index}" => 'File gagal disimpan. Silakan unggah ulang.',
                    ]);
                }

                $fileBaru[$jenis] = $path;
            }

            DB::transaction(function () use ($template, $fileBaru, &$fileLama) {
                foreach ($fileBaru as $jenis => $path) {
                    $asetLama = $template->aset()->where('jenis', $jenis)->first();

                    if ($asetLama?->path) {
                        $fileLama[] = $asetLama->path;
                    }

                    $template->aset()->updateOrCreate(['jenis' => $jenis], ['path' => $path]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values($fileBaru));

            throw $exception;
        }

        Storage::disk('local')->delete(array_diff($fileLama, array_values($fileBaru)));

        return count($fileBaru);
    }

    private function simpanFileLayerVideo(TemplateVisual $template, array $uploads): int
    {
        $fileBaru = [];
        $fileLama = [];

        try {
            foreach ($uploads as $sceneIndex => $layers) {
                foreach (array_filter($layers ?? []) as $layerIndex => $file) {
                    $layer = $this->videoScenes[$sceneIndex]['layers'][$layerIndex] ?? null;
                    if (! is_array($layer) || ($layer['jenis'] ?? '') !== 'png') {
                        continue;
                    }
                    $jenis = 'video_scene_'.($sceneIndex + 1).'_'.($layer['id'] ?? "layer_{$layerIndex}");
                    $path = $file->store("template-visual/{$template->id}/video", 'local');
                    if (! $path || ! Storage::disk('local')->exists($path)) {
                        throw ValidationException::withMessages([
                            "videoLayerUploads.{$sceneIndex}.{$layerIndex}" => 'Aset PNG gagal disimpan.',
                        ]);
                    }
                    $fileBaru[$jenis] = $path;
                }
            }

            DB::transaction(function () use ($template, $fileBaru, &$fileLama) {
                foreach ($fileBaru as $jenis => $path) {
                    $asetLama = $template->aset()->where('jenis', $jenis)->first();
                    if ($asetLama?->path) {
                        $fileLama[] = $asetLama->path;
                    }
                    $template->aset()->updateOrCreate(['jenis' => $jenis], ['path' => $path]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values($fileBaru));
            throw $exception;
        }

        Storage::disk('local')->delete(array_diff($fileLama, array_values($fileBaru)));

        return count($fileBaru);
    }

    public function aktifkan(int $templateId): void
    {
        Gate::authorize('kelola_template_visual');
        $template = TemplateVisual::with(['layouts', 'aset'])->findOrFail($templateId);

        if (! $template->layouts->contains('jenis', 'cover') || ! $template->layouts->contains('jenis', 'isi')) {
            $this->addError('template', 'Template harus memiliki layout cover dan isi sebelum diaktifkan.');

            return;
        }

        if ($template->format === 'ig_carousel' && $template->aset->whereIn('jenis', ['background_slide_1', 'background_slide_2', 'background_slide_3'])->count() !== 3) {
            $this->addError('template', 'Template carousel wajib memiliki tepat tiga background PNG sebelum tersedia di editor.');

            return;
        }

        if ($template->format === 'video_vertikal' && collect([1, 2, 3])->contains(fn (int $urutan) => ! $template->layouts->contains('jenis', "video_scene_{$urutan}"))) {
            $this->addError('template', 'Template video wajib memiliki tepat tiga scene sebelum tersedia di editor.');

            return;
        }

        DB::transaction(function () use ($template) {
            TemplateVisual::query()
                ->where('format', $template->format)
                ->where('nama', $template->nama)
                ->where('status', 'aktif')
                ->whereKeyNot($template->id)
                ->update(['status' => 'arsip']);
            $template->update(['status' => 'aktif']);
        });

        $this->pilihTemplate($template->id);
        session()->flash('template-tersimpan', "Template {$template->nama} v{$template->versi} sudah aktif.");
    }

    /** @return array<string, array<int, mixed>> */
    private function aturanValidasi(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'format' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'rasio' => ['required', 'string', 'max:10', 'regex:/^\d{1,2}:\d{1,2}$/'],
            'durasiPerSlide' => ['nullable', 'integer', 'between:1,60'],
            'coverKicker' => ['required', 'integer', 'between:0,500'],
            'coverJudul' => ['required', 'integer', 'between:1,500'],
            'coverIsi' => ['required', 'integer', 'between:0,2000'],
            'isiKicker' => ['required', 'integer', 'between:0,500'],
            'isiJudul' => ['required', 'integer', 'between:1,500'],
            'isiTeks' => ['required', 'integer', 'between:0,2000'],
            'backgroundSlides' => ['array', 'max:3'],
            'backgroundSlides.*' => ['nullable', 'image', 'mimes:png', 'dimensions:width=1080,height=1350', 'max:10240'],
            'videoLayerUploads' => ['array'],
            'videoLayerUploads.*.*' => ['nullable', 'image', 'mimes:png', 'max:10240'],
            'videoScenes' => ['required', 'array', 'size:3'],
            'videoScenes.*.durasi' => ['required', 'integer', 'between:3,15'],
            'videoScenes.*.layers' => ['required', 'array', 'min:1', 'max:20'],
            'videoScenes.*.layers.*.id' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_]+$/'],
            'videoScenes.*.layers.*.nama' => ['required', 'string', 'max:60'],
            'videoScenes.*.layers.*.jenis' => ['required', 'in:png,foto,judul,paragraf'],
            'videoScenes.*.layers.*.x' => ['required', 'integer', 'between:-1080,1079'],
            'videoScenes.*.layers.*.y' => ['required', 'integer', 'between:-1920,1919'],
            'videoScenes.*.layers.*.lebar' => ['required', 'integer', 'between:40,1080'],
            'videoScenes.*.layers.*.tinggi' => ['required', 'integer', 'between:40,1920'],
            'videoScenes.*.layers.*.urutan' => ['required', 'integer', 'between:0,100'],
            'videoScenes.*.layers.*.animasi' => ['required', 'in:diam,fade_in,masuk_kiri,masuk_kanan,naik,zoom_lembut'],
            'videoScenes.*.layers.*.mulai' => ['required', 'numeric', 'between:0,15'],
            'videoScenes.*.layers.*.durasi_animasi' => ['required', 'numeric', 'between:0,3'],
            'penempatanSlides' => ['required', 'array', 'size:3'],
            'penempatanSlides.*.foto_slots' => ['required', 'array'],
            'penempatanSlides.*.foto_slots.*.x' => ['required', 'integer', 'between:0,1079'],
            'penempatanSlides.*.foto_slots.*.y' => ['required', 'integer', 'between:0,1349'],
            'penempatanSlides.*.foto_slots.*.lebar' => ['required', 'integer', 'between:40,1080'],
            'penempatanSlides.*.foto_slots.*.tinggi' => ['required', 'integer', 'between:40,1350'],
            'penempatanSlides.*.foto_slots.*.radius' => ['required', 'integer', 'between:0,200'],
            'penempatanSlides.*.teks.x' => ['required', 'integer', 'between:0,1079'],
            'penempatanSlides.*.teks.y' => ['required', 'integer', 'between:0,1349'],
            'penempatanSlides.*.teks.lebar' => ['required', 'integer', 'between:40,1080'],
            'penempatanSlides.*.teks.tinggi' => ['required', 'integer', 'between:40,1350'],
        ];
    }

    private function pastikanPenempatanDiDalamKanvas(array $slides): void
    {
        foreach ($slides as $index => $slide) {
            foreach ([...$slide['foto_slots'], $slide['teks']] as $kotak) {
                if ($kotak['x'] + $kotak['lebar'] > 1080 || $kotak['y'] + $kotak['tinggi'] > 1350) {
                    throw ValidationException::withMessages([
                        "penempatanSlides.{$index}" => 'Area foto dan teks harus tetap berada di dalam kanvas 1080 × 1350.',
                    ]);
                }
            }
        }
    }

    private function pastikanVideoDiDalamKanvas(array $scenes): void
    {
        foreach ($scenes as $sceneIndex => $scene) {
            foreach ($scene['layers'] as $layerIndex => $layer) {
                if (
                    $layer['x'] + $layer['lebar'] <= 0
                    || $layer['y'] + $layer['tinggi'] <= 0
                    || $layer['x'] >= 1080
                    || $layer['y'] >= 1920
                ) {
                    throw ValidationException::withMessages([
                        "videoScenes.{$sceneIndex}.layers.{$layerIndex}" => 'Layer boleh memakai posisi negatif, tetapi sebagian layer harus tetap menyentuh kanvas 1080 × 1920.',
                    ]);
                }
                if ((float) $layer['mulai'] + (float) $layer['durasi_animasi'] > (int) $scene['durasi']) {
                    throw ValidationException::withMessages([
                        "videoScenes.{$sceneIndex}.layers.{$layerIndex}.mulai" => 'Animasi harus selesai sebelum durasi scene berakhir.',
                    ]);
                }
            }
        }
    }

    private function versiBerikutnya(string $nama): int
    {
        $versiTerakhir = TemplateVisual::where('nama', $nama)
            ->orderByDesc('versi')
            ->lockForUpdate()
            ->value('versi');

        return ((int) $versiTerakhir) + 1;
    }

    public function render()
    {
        $templates = TemplateVisual::with(['aset', 'layouts'])->orderBy('format')->orderBy('nama')->orderByDesc('versi')->get();
        $templateAktif = $this->templateId ? $templates->firstWhere('id', $this->templateId) : null;

        return view('livewire.kelola-template-visual', compact('templates', 'templateAktif'));
    }
}
