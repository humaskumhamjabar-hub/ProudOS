<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Agenda\Models\Agenda;

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

    public function mount(): void
    {
        Gate::authorize('kelola_agenda');
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

        return view('livewire.kelola-agenda', ['agenda' => $agenda]);
    }
}
