<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Agenda\Models\Agenda;
use Modules\People\Models\User;
use Modules\Scheduling\Actions\BuatPenugasan;
use Modules\Scheduling\Models\Penugasan;
use Modules\Scheduling\Models\PeranProduksi;
use Modules\Work\Models\Tugas;

#[Layout('components.layouts.app')]
class KelolaTugas extends Component
{
    public bool $formTerbuka = false;

    public ?int $tugasId = null;

    public string $judul = '';

    public string $brief = '';

    public string $deadlineAt = '';

    public string $agendaId = '';

    public string $status = 'baru';

    public string $filterStatus = 'aktif';

    public ?int $tugasTimId = null;

    public string $anggotaId = '';

    public string $peranId = '';

    public string $pembimbingId = '';

    public string $deadlinePenugasanAt = '';

    public string $catatanPenugasan = '';

    public function mount(): void
    {
        Gate::authorize('kelola_tugas');
    }

    public function buat(): void
    {
        Gate::authorize('kelola_tugas');
        $this->resetForm();
        $this->formTerbuka = true;
    }

    public function edit(int $tugasId): void
    {
        Gate::authorize('kelola_tugas');
        $tugas = Tugas::findOrFail($tugasId);

        $this->tugasId = $tugas->id;
        $this->judul = $tugas->judul;
        $this->brief = $tugas->brief ?? '';
        $this->deadlineAt = $tugas->deadline_at?->format('Y-m-d\TH:i') ?? '';
        $this->agendaId = $tugas->agenda_id ? (string) $tugas->agenda_id : '';
        $this->status = $tugas->status;
        $this->formTerbuka = true;
    }

    public function simpan(): void
    {
        Gate::authorize('kelola_tugas');

        $data = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'brief' => ['nullable', 'string', 'max:10000'],
            'deadlineAt' => ['nullable', 'date'],
            'agendaId' => ['nullable', 'integer', 'exists:agendas,id'],
            'status' => ['required', Rule::in(['baru', 'dikerjakan', 'selesai'])],
        ]);

        $atribut = [
            'judul' => $data['judul'],
            'brief' => $data['brief'] ?: null,
            'deadline_at' => $data['deadlineAt'] ?: null,
            'agenda_id' => $data['agendaId'] ?: null,
            'status' => $data['status'],
        ];

        if ($this->tugasId) {
            Tugas::findOrFail($this->tugasId)->update($atribut);
        } else {
            Tugas::create($atribut + ['dibuat_oleh' => Auth::id()]);
        }

        $this->tutupForm();
        session()->flash('sukses', 'Tugas berhasil disimpan.');
    }

    public function aturPelaksana(int $tugasId): void
    {
        Gate::authorize('kelola_tugas');
        $tugas = Tugas::findOrFail($tugasId);

        $this->resetValidation();
        $this->tugasTimId = $tugas->id;
        $this->anggotaId = '';
        $this->peranId = '';
        $this->pembimbingId = '';
        $this->deadlinePenugasanAt = $tugas->deadline_at?->format('Y-m-d\TH:i') ?? '';
        $this->catatanPenugasan = '';
    }

    public function simpanPenugasan(BuatPenugasan $buat): void
    {
        Gate::authorize('kelola_tugas');
        $tugas = Tugas::findOrFail($this->tugasTimId);

        $data = $this->validate([
            'anggotaId' => ['required', 'integer', 'exists:users,id'],
            'peranId' => ['required', 'integer', 'exists:peran_produksi,id'],
            'pembimbingId' => ['nullable', 'integer', 'different:anggotaId', 'exists:users,id'],
            'deadlinePenugasanAt' => ['required', 'date'],
            'catatanPenugasan' => ['nullable', 'string', 'max:2000'],
        ]);

        $anggota = User::where('status', 'aktif')->findOrFail($data['anggotaId']);
        PeranProduksi::where('aktif', true)->findOrFail($data['peranId']);

        if ($anggota->batch_id && ! $data['pembimbingId']) {
            throw ValidationException::withMessages([
                'pembimbingId' => 'Pilih pembimbing untuk peserta magang.',
            ]);
        }

        if ($data['pembimbingId']) {
            User::where('status', 'aktif')->findOrFail($data['pembimbingId']);
        }

        $buat->handle([
            'user_id' => (int) $data['anggotaId'],
            'tipe' => 'berdeadline',
            'deadline_at' => $data['deadlinePenugasanAt'],
            'untuk_type' => 'tugas',
            'untuk_id' => $tugas->id,
            'peran_id' => (int) $data['peranId'],
            'pembimbing_id' => $data['pembimbingId'] ? (int) $data['pembimbingId'] : null,
            'status' => 'aktif',
            'catatan' => $data['catatanPenugasan'] ?: null,
        ]);

        $this->aturPelaksana($tugas->id);
        session()->flash('sukses', 'Pelaksana berhasil ditambahkan.');
    }

    public function batalkanPenugasan(int $penugasanId): void
    {
        Gate::authorize('kelola_tugas');

        Penugasan::query()
            ->where('untuk_type', 'tugas')
            ->where('untuk_id', $this->tugasTimId)
            ->where('status', 'aktif')
            ->findOrFail($penugasanId)
            ->update(['status' => 'batal']);

        session()->flash('sukses', 'Penugasan dibatalkan.');
    }

    public function tutupForm(): void
    {
        $this->resetForm();
        $this->formTerbuka = false;
    }

    public function tutupPelaksana(): void
    {
        $this->tugasTimId = null;
        $this->resetValidation();
    }

    public function render()
    {
        Gate::authorize('kelola_tugas');

        $query = Tugas::query()->latest('updated_at');
        if ($this->filterStatus === 'aktif') {
            $query->whereIn('status', ['baru', 'dikerjakan']);
        } elseif ($this->filterStatus !== 'semua') {
            $query->where('status', $this->filterStatus);
        }

        $tugas = $query->get();
        $penugasan = Penugasan::with('peran')
            ->where('untuk_type', 'tugas')
            ->whereIn('untuk_id', $tugas->pluck('id'))
            ->where('status', '!=', 'batal')
            ->orderBy('deadline_at')
            ->get()
            ->groupBy('untuk_id');

        $orangIds = $penugasan->flatten(1)->pluck('user_id')
            ->merge($penugasan->flatten(1)->pluck('pembimbing_id'))
            ->filter()->unique();
        $namaOrang = User::whereIn('id', $orangIds)->pluck('nama', 'id');

        return view('livewire.kelola-tugas', [
            'tugas' => $tugas,
            'penugasan' => $penugasan,
            'namaOrang' => $namaOrang,
            'agenda' => Agenda::whereIn('status', ['rencana', 'berjalan'])->orderBy('mulai_at')->get(),
            'anggota' => User::where('status', 'aktif')->orderBy('nama')->get(),
            'peran' => PeranProduksi::where('aktif', true)->orderBy('nama')->get(),
        ]);
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->tugasId = null;
        $this->judul = '';
        $this->brief = '';
        $this->deadlineAt = '';
        $this->agendaId = '';
        $this->status = 'baru';
    }
}
