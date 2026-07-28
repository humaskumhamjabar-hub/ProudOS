<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Agenda\Models\Agenda;
use Modules\People\Models\User;
use Modules\Scheduling\Actions\BuatPenugasan;
use Modules\Scheduling\Models\Penugasan;
use Modules\Scheduling\Models\PeranProduksi;

#[Layout('components.layouts.app')]
class KelolaAgenda extends Component
{
    public ?int $agendaId = null;

    public bool $formTerbuka = false;

    public string $judul = '';

    public string $deskripsi = '';

    public string $mulaiAt = '';

    public string $selesaiAt = '';

    public string $lokasi = '';

    public array $kebutuhanHumas = [];

    public string $status = 'rencana';

    public string $filterStatus = 'aktif';

    #[Locked]
    public ?int $agendaTimId = null;

    public int|string $anggotaId = '';

    public int|string $peranId = '';

    #[Locked]
    public bool $bolehTerobos = false;

    public function mount(): void
    {
        Gate::authorize('kelola_agenda');
    }

    public function aturTim(int $agendaId): void
    {
        Gate::authorize('kelola_penugasan');

        $this->agendaTimId = Agenda::findOrFail($agendaId)->id;
        $this->reset(['anggotaId', 'peranId', 'bolehTerobos']);
    }

    public function simpanPenugasan(BuatPenugasan $buat): void
    {
        $this->simpanPenugasanDengan($buat);
    }

    private function simpanPenugasanDengan(BuatPenugasan $buat, bool $terobos = false): void
    {
        Gate::authorize('kelola_penugasan');

        $data = $this->validate([
            'agendaTimId' => ['required', 'integer', 'exists:agendas,id'],
            'anggotaId' => ['required', 'integer', Rule::exists('users', 'id')->where('status', 'aktif')->whereNull('deleted_at')],
            'peranId' => ['required', 'integer', Rule::exists('peran_produksi', 'id')->where('aktif', true)],
        ]);
        $agenda = Agenda::findOrFail($data['agendaTimId']);

        if (! $agenda->selesai_at) {
            $this->addError('agendaTimId', 'Agenda harus memiliki waktu selesai sebelum tim ditugaskan.');

            return;
        }

        try {
            $buat->handle([
                'user_id' => User::findOrFail($data['anggotaId'])->id,
                'peran_id' => PeranProduksi::findOrFail($data['peranId'])->id,
                'tipe' => 'berjam',
                'mulai_at' => $agenda->mulai_at,
                'selesai_at' => $agenda->selesai_at,
                'untuk_type' => 'agenda',
                'untuk_id' => $agenda->id,
                'status' => 'aktif',
            ], $terobos);
            $this->bolehTerobos = false;
        } catch (ValidationException $exception) {
            if (str_starts_with($exception->errors()['user_id'][0] ?? '', 'Bentrok jam')) {
                $this->bolehTerobos = true;
            }

            throw $exception;
        }
    }

    public function terobosBentrok(BuatPenugasan $buat): void
    {
        Gate::authorize('kelola_penugasan');
        abort_unless($this->bolehTerobos, 403);

        // ponytail: bind confirmation to the current server-validated selection;
        // add a signed nonce only if multiple concurrent editors become real.
        $this->simpanPenugasanDengan($buat, true);
    }

    public function batalkanPenugasan(int $id): void
    {
        Gate::authorize('kelola_penugasan');

        Penugasan::where('untuk_type', 'agenda')
            ->where('untuk_id', $this->agendaTimId)
            ->where('status', 'aktif')
            ->findOrFail($id)
            ->update(['status' => 'batal']);
    }

    public function updatedAgendaTimId(): void
    {
        $this->bolehTerobos = false;
    }

    public function updatedAnggotaId(): void
    {
        $this->bolehTerobos = false;
    }

    public function updatedPeranId(): void
    {
        $this->bolehTerobos = false;
    }

    public function buat(): void
    {
        Gate::authorize('kelola_agenda');

        $this->resetForm();
        $this->mulaiAt = now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
        $this->formTerbuka = true;
    }

    public function edit(int $agendaId): void
    {
        Gate::authorize('kelola_agenda');

        $agenda = Agenda::findOrFail($agendaId);

        $this->agendaId = $agenda->id;
        $this->judul = $agenda->judul;
        $this->deskripsi = $agenda->deskripsi ?? '';
        $this->mulaiAt = $agenda->mulai_at->format('Y-m-d\TH:i');
        $this->selesaiAt = $agenda->selesai_at?->format('Y-m-d\TH:i') ?? '';
        $this->lokasi = $agenda->lokasi ?? '';
        $this->kebutuhanHumas = $agenda->kebutuhan_humas ?? [];
        $this->status = $agenda->status;
        $this->formTerbuka = true;
        $this->resetValidation();
    }

    public function simpan(): void
    {
        Gate::authorize('kelola_agenda');

        $data = $this->validate([
            'judul' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'mulaiAt' => ['required', 'date'],
            'selesaiAt' => ['nullable', 'date', 'after:mulaiAt'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'kebutuhanHumas' => ['array'],
            'kebutuhanHumas.*' => [Rule::in(['foto', 'video', 'berita', 'caption'])],
            'status' => ['required', Rule::in(['rencana', 'berjalan', 'selesai', 'batal'])],
        ], [], [
            'mulaiAt' => 'waktu mulai',
            'selesaiAt' => 'waktu selesai',
            'kebutuhanHumas' => 'kebutuhan Humas',
        ]);

        $atribut = [
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?: null,
            'mulai_at' => $data['mulaiAt'],
            'selesai_at' => $data['selesaiAt'] ?: null,
            'lokasi' => $data['lokasi'] ?: null,
            'kebutuhan_humas' => array_values($data['kebutuhanHumas']),
            'status' => $data['status'],
        ];

        if ($this->agendaId) {
            Agenda::findOrFail($this->agendaId)->update($atribut);
            $pesan = 'Agenda berhasil diperbarui.';
        } else {
            Agenda::create($atribut + ['dibuat_oleh' => Auth::id()]);
            $pesan = 'Agenda berhasil dibuat.';
        }

        $this->resetForm();
        session()->flash('agenda-tersimpan', $pesan);
    }

    public function tutupForm(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'agendaId', 'formTerbuka', 'judul', 'deskripsi', 'mulaiAt',
            'selesaiAt', 'lokasi', 'kebutuhanHumas',
        ]);
        $this->status = 'rencana';
        $this->resetValidation();
    }

    public function render()
    {
        $agenda = Agenda::query()
            ->when(
                $this->filterStatus === 'aktif',
                fn ($query) => $query->whereIn('status', ['rencana', 'berjalan']),
                fn ($query) => $query->where('status', $this->filterStatus),
            )
            ->orderBy('mulai_at')
            ->get();

        $agendaTim = $this->agendaTimId ? Agenda::findOrFail($this->agendaTimId) : null;
        $penugasan = $agendaTim
            ? Penugasan::with('peran')->where('untuk_type', 'agenda')->where('untuk_id', $agendaTim->id)->get()
            : collect();

        return view('livewire.kelola-agenda', [
            'agenda' => $agenda,
            'agendaTim' => $agendaTim,
            'anggota' => $agendaTim ? User::where('status', 'aktif')->orderBy('nama')->get() : collect(),
            'peran' => $agendaTim ? PeranProduksi::where('aktif', true)->orderBy('nama')->get() : collect(),
            'penugasan' => $penugasan,
            // ponytail: read names in one map; add the model relation only when another caller needs it.
            'namaAnggota' => User::withTrashed()->whereIn('id', $penugasan->pluck('user_id'))->pluck('nama', 'id'),
        ]);
    }
}
