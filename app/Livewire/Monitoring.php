<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Monitoring\Models\Temuan;
use Modules\People\Models\User;

#[Layout('components.layouts.app')]
class Monitoring extends Component
{
    public string $cari = '';

    public string $filterStatus = 'semua';

    public string $filterSentimen = 'semua';

    public bool $formTerbuka = false;

    public ?int $temuanId = null;

    public string $sumber = '';

    public string $ringkasan = '';

    public string $url = '';

    public string $sentimen = 'netral';

    public string $tanggal = '';

    public string $statusTindakLanjut = 'baru';

    public ?int $picId = null;

    /** @var array<int, string> */
    public array $aksi = [];

    public function mount(): void
    {
        Gate::authorize('kelola_monitoring');
        $this->tanggal = now()->toDateString();
    }

    public function buat(): void
    {
        Gate::authorize('kelola_monitoring');
        $this->resetForm();
        $this->formTerbuka = true;
    }

    public function edit(int $temuanId): void
    {
        Gate::authorize('kelola_monitoring');
        $temuan = Temuan::findOrFail($temuanId);
        $this->temuanId = $temuan->id;
        $this->sumber = $temuan->sumber;
        $this->ringkasan = $temuan->ringkasan;
        $this->url = $temuan->url ?? '';
        $this->sentimen = $temuan->sentimen;
        $this->tanggal = $temuan->tanggal->toDateString();
        $this->statusTindakLanjut = $temuan->status_tindak_lanjut;
        $this->picId = $temuan->pic_id;
        $this->formTerbuka = true;
        $this->resetValidation();
    }

    public function simpan(): void
    {
        Gate::authorize('kelola_monitoring');
        $data = $this->validate([
            'sumber' => ['required', 'string', 'max:100'],
            'ringkasan' => ['required', 'string', 'max:2000'],
            'url' => ['nullable', 'url:http,https', 'max:2000'],
            'sentimen' => ['required', Rule::in(['positif', 'netral', 'negatif'])],
            'tanggal' => ['required', 'date'],
            'statusTindakLanjut' => ['required', Rule::in(['baru', 'diproses', 'selesai'])],
            'picId' => ['nullable', 'integer', Rule::exists('users', 'id')->where('status', 'aktif')],
        ]);

        $atribut = [
            'sumber' => $data['sumber'],
            'ringkasan' => $data['ringkasan'],
            'url' => filled($data['url'] ?? null) ? $data['url'] : null,
            'sentimen' => $data['sentimen'],
            'tanggal' => $data['tanggal'],
            'status_tindak_lanjut' => $data['statusTindakLanjut'],
            'pic_id' => $data['picId'] ?? null,
        ];

        $temuan = $this->temuanId ? Temuan::findOrFail($this->temuanId) : new Temuan;
        $temuan->fill($atribut)->save();

        $this->tutupForm();
        session()->flash('monitoring-sukses', 'Temuan tersimpan.');
    }

    public function tambahTindakLanjut(int $temuanId): void
    {
        Gate::authorize('kelola_monitoring');
        $this->validate(["aksi.{$temuanId}" => ['required', 'string', 'max:2000']]);
        $temuan = Temuan::findOrFail($temuanId);
        $temuan->tindakLanjut()->create([
            'aksi' => trim($this->aksi[$temuanId]),
            'oleh_id' => Auth::id(),
            'at' => now(),
        ]);

        if ($temuan->status_tindak_lanjut === 'baru') {
            $temuan->update(['status_tindak_lanjut' => 'diproses']);
        }

        unset($this->aksi[$temuanId]);
        session()->flash('monitoring-sukses', 'Tindak lanjut tercatat.');
    }

    public function tandaiSelesai(int $temuanId): void
    {
        Gate::authorize('kelola_monitoring');
        Temuan::findOrFail($temuanId)->update(['status_tindak_lanjut' => 'selesai']);
        session()->flash('monitoring-sukses', 'Temuan ditandai selesai.');
    }

    public function tutupForm(): void
    {
        $this->formTerbuka = false;
        $this->resetForm();
    }

    public function render()
    {
        $query = Temuan::with('tindakLanjut')->latest('tanggal')->latest('id');

        if ($this->filterStatus !== 'semua') {
            $query->where('status_tindak_lanjut', $this->filterStatus);
        }
        if ($this->filterSentimen !== 'semua') {
            $query->where('sentimen', $this->filterSentimen);
        }
        if (filled(trim($this->cari))) {
            $cari = '%'.trim($this->cari).'%';
            $query->where(fn ($q) => $q->where('sumber', 'like', $cari)->orWhere('ringkasan', 'like', $cari));
        }

        $pengguna = User::where('status', 'aktif')->orderBy('nama')->get(['id', 'nama']);

        return view('livewire.monitoring', [
            'temuan' => $query->get(),
            'pengguna' => $pengguna,
            'namaPengguna' => $pengguna->pluck('nama', 'id'),
        ]);
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->temuanId = null;
        $this->sumber = '';
        $this->ringkasan = '';
        $this->url = '';
        $this->sentimen = 'netral';
        $this->tanggal = now()->toDateString();
        $this->statusTindakLanjut = 'baru';
        $this->picId = null;
    }
}
