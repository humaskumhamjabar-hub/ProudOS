<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Library\Models\Pustaka as DokumenPustaka;

#[Layout('components.layouts.app')]
class Pustaka extends Component
{
    use WithFileUploads;

    public string $cari = '';

    public string $kategoriFilter = 'semua';

    public bool $formTerbuka = false;

    public ?int $pustakaId = null;

    public string $judul = '';

    public string $kategori = 'sop';

    public string $tipe = 'teks';

    public string $isi = '';

    public $berkas = null;

    public function buat(): void
    {
        Gate::authorize('kelola_pustaka');
        $this->resetForm();
        $this->formTerbuka = true;
    }

    public function edit(int $pustakaId): void
    {
        Gate::authorize('kelola_pustaka');
        $dokumen = DokumenPustaka::findOrFail($pustakaId);

        $this->pustakaId = $dokumen->id;
        $this->judul = $dokumen->judul;
        $this->kategori = $dokumen->kategori;
        $this->tipe = $dokumen->tipe;
        $this->isi = $dokumen->isi ?? '';
        $this->berkas = null;
        $this->formTerbuka = true;
    }

    public function simpan(): void
    {
        Gate::authorize('kelola_pustaka');

        $rules = [
            'judul' => ['required', 'string', 'max:255'],
            'kategori' => ['required', Rule::in(array_keys($this->kategoriOptions()))],
            'tipe' => ['required', Rule::in(['teks', 'file'])],
            'isi' => ['nullable', 'required_if:tipe,teks', 'string', 'max:100000'],
        ];

        if ($this->tipe === 'file') {
            $rules['berkas'] = [$this->pustakaId ? 'nullable' : 'required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png'];
        }

        $data = $this->validate($rules);

        $lama = $this->pustakaId ? DokumenPustaka::findOrFail($this->pustakaId) : null;
        $pathLama = $lama?->path;
        $path = $pathLama;

        if ($data['tipe'] === 'file' && $this->berkas) {
            $pathBaru = $this->berkas->store('pustaka');
            $path = $pathBaru;
        } elseif ($data['tipe'] === 'teks') {
            $path = null;
        }

        $atribut = [
            'judul' => $data['judul'],
            'kategori' => $data['kategori'],
            'tipe' => $data['tipe'],
            'path' => $path,
            'isi' => $data['tipe'] === 'teks' ? $data['isi'] : null,
            'versi' => $lama ? $lama->versi + 1 : 1,
        ];

        try {
            if ($lama) {
                $lama->update($atribut);
            } else {
                DokumenPustaka::create($atribut + ['dibuat_oleh' => Auth::id()]);
            }
        } catch (\Throwable $exception) {
            if (isset($pathBaru)) {
                Storage::disk('local')->delete($pathBaru);
            }

            throw $exception;
        }

        if ($pathLama && $pathLama !== $path) {
            Storage::disk('local')->delete($pathLama);
        }

        $this->tutupForm();
        session()->flash('sukses', 'Pustaka berhasil disimpan.');
    }

    public function unduh(int $pustakaId)
    {
        $dokumen = DokumenPustaka::where('tipe', 'file')->findOrFail($pustakaId);
        abort_unless($dokumen->path && Storage::disk('local')->exists($dokumen->path), 404);

        return Storage::disk('local')->download($dokumen->path, basename($dokumen->path));
    }

    public function tutupForm(): void
    {
        $this->formTerbuka = false;
        $this->resetForm();
    }

    public function render()
    {
        $query = DokumenPustaka::query()->latest('updated_at');

        if ($this->kategoriFilter !== 'semua') {
            $query->where('kategori', $this->kategoriFilter);
        }

        if (trim($this->cari) !== '') {
            $cari = '%'.trim($this->cari).'%';
            $query->where(fn ($q) => $q->where('judul', 'like', $cari)->orWhere('isi', 'like', $cari));
        }

        return view('livewire.pustaka', [
            'dokumen' => $query->get(),
            'kategoriOptions' => $this->kategoriOptions(),
        ]);
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->pustakaId = null;
        $this->judul = '';
        $this->kategori = 'sop';
        $this->tipe = 'teks';
        $this->isi = '';
        $this->berkas = null;
    }

    private function kategoriOptions(): array
    {
        return [
            'sop' => 'SOP',
            'template' => 'Template',
            'pedoman' => 'Pedoman',
            'onboarding' => 'Onboarding',
            'referensi' => 'Referensi',
        ];
    }
}
