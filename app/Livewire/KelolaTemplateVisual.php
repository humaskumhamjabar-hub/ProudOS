<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Visual\Models\TemplateVisual;

#[Layout('components.layouts.app')]
class KelolaTemplateVisual extends Component
{
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

    public function mount(): void
    {
        Gate::authorize('kelola_template_visual');
        $template = TemplateVisual::with('layouts')->latest('updated_at')->first();

        if ($template) {
            $this->pilihTemplate($template->id);
        }
    }

    public function pilihTemplate(int $templateId): void
    {
        Gate::authorize('kelola_template_visual');
        $template = TemplateVisual::with('layouts')->findOrFail($templateId);
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
        $this->resetValidation();
    }

    public function buatVersiBaru(int $templateId): void
    {
        Gate::authorize('kelola_template_visual');
        $asal = TemplateVisual::with('layouts')->findOrFail($templateId);

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

            return $baru;
        });

        $this->pilihTemplate($baru->id);
        session()->flash('template-tersimpan', "Versi {$baru->versi} dibuat sebagai draf. Tinjau preview sebelum mengaktifkan.");
    }

    public function simpanDraf(): void
    {
        Gate::authorize('kelola_template_visual');
        $template = $this->templateId ? TemplateVisual::findOrFail($this->templateId) : null;

        if ($template?->status === 'aktif') {
            $this->addError('template', 'Template aktif tidak diubah langsung. Buat versi baru terlebih dahulu.');

            return;
        }

        $data = $this->validate($this->aturanValidasi());

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

            return $template;
        });

        $this->pilihTemplate($template->id);
        session()->flash('template-tersimpan', 'Draf template tersimpan. Periksa preview sebelum mengaktifkan.');
    }

    public function aktifkan(int $templateId): void
    {
        Gate::authorize('kelola_template_visual');
        $template = TemplateVisual::with('layouts')->findOrFail($templateId);

        if (! $template->layouts->contains('jenis', 'cover') || ! $template->layouts->contains('jenis', 'isi')) {
            $this->addError('template', 'Template harus memiliki layout cover dan isi sebelum diaktifkan.');

            return;
        }

        DB::transaction(function () use ($template) {
            TemplateVisual::where('format', $template->format)
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
        ];
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
        $templates = TemplateVisual::orderBy('format')->orderBy('nama')->orderByDesc('versi')->get();
        $templateAktif = $this->templateId ? $templates->firstWhere('id', $this->templateId) : null;

        return view('livewire.kelola-template-visual', compact('templates', 'templateAktif'));
    }
}
