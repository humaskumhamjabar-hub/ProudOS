<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Agenda\Models\Agenda;
use Modules\Content\Models\CatatanPembimbing;
use Modules\People\Models\User;
use Modules\Scheduling\Actions\KonfirmasiPenugasan;
use Modules\Scheduling\Models\Penugasan;
use Modules\Work\Models\Tugas;

/**
 * Layar kerja generik Rilis 1: brief, bahan, komentar, status.
 * Saat modul content hadir, tugas dengan subjek paket_konten membuka layar
 * kerja milik modul content — tombolnya tetap satu.
 */
#[Layout('components.layouts.app')]
class KerjakanTugas extends Component
{
    use WithFileUploads;

    public Tugas $tugas;

    public $unggahan = [];

    public string $komentarBaru = '';

    public string $catatanPembimbingBaru = '';

    public function mount(int $tugasId, KonfirmasiPenugasan $konfirmasi): void
    {
        $this->tugas = Tugas::with(['bahan', 'komentar'])->findOrFail($tugasId);
        Gate::authorize('lihat-tugas', $this->tugas);

        // Membuka layar kerja = penugasan terkait tercatat dibaca.
        Penugasan::where('user_id', Auth::id())
            ->where('untuk_type', 'tugas')
            ->where('untuk_id', $this->tugas->id)
            ->whereNull('dibaca_at')
            ->get()
            ->each(fn (Penugasan $p) => $konfirmasi->tandaiDibaca($p));

        if ($this->tugas->subjek_type === 'paket_konten' && $this->tugas->subjek_id) {
            $this->redirectRoute('produksi.index', ['paket' => $this->tugas->subjek_id], navigate: true);
        }
    }

    public function mulaiKerjakan(): void
    {
        $this->authorizeWork();

        if ($this->tugas->status === 'baru') {
            $this->tugas->update(['status' => 'dikerjakan']);
        }
    }

    public function tandaiSelesai(): void
    {
        $this->authorizeWork();

        Penugasan::query()
            ->where('user_id', Auth::id())
            ->where('untuk_type', 'tugas')
            ->where('untuk_id', $this->tugas->id)
            ->where('status', 'aktif')
            ->update(['status' => 'selesai']);

        if (! Penugasan::query()
            ->where('untuk_type', 'tugas')
            ->where('untuk_id', $this->tugas->id)
            ->where('status', 'aktif')
            ->exists()) {
            $this->tugas->update(['status' => 'selesai']);
        }
    }

    public function unggahBahan(): void
    {
        $this->authorizeWork();
        $this->validate(['unggahan.*' => 'file|max:51200']);

        foreach ($this->unggahan as $file) {
            $path = $file->store('tugas-bahan/'.$this->tugas->id);
            $this->tugas->bahan()->create([
                'path' => $path,
                'nama_asli' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?? 'application/octet-stream',
                'diunggah_oleh' => Auth::id(),
            ]);
        }

        $this->unggahan = [];
        $this->tugas->refresh();
        $this->mulaiKerjakan();
    }

    public function kirimKomentar(): void
    {
        $this->authorizeWork();
        $this->validate(['komentarBaru' => 'required|string|max:2000']);

        $this->tugas->komentar()->create([
            'user_id' => Auth::id(),
            'isi' => $this->komentarBaru,
        ]);

        $this->komentarBaru = '';
        $this->tugas->refresh();
    }

    public function simpanCatatanPembimbing(): void
    {
        Gate::authorize('lihat-tugas', $this->tugas);
        $this->validate(['catatanPembimbingBaru' => ['required', 'string', 'max:2000']]);

        $penugasan = Penugasan::query()
            ->where('untuk_type', 'tugas')
            ->where('untuk_id', $this->tugas->id)
            ->where('status', '!=', 'batal')
            ->when(
                ! Auth::user()->can('kelola_tugas'),
                fn ($query) => $query->where('pembimbing_id', Auth::id()),
            )
            ->firstOrFail();

        CatatanPembimbing::create([
            'penugasan_id' => $penugasan->id,
            'isi' => $this->catatanPembimbingBaru,
            'oleh_id' => Auth::id(),
        ]);

        $this->catatanPembimbingBaru = '';
    }

    public function render()
    {
        // Lapisan baca boleh melihat modul agenda untuk konteks kegiatan.
        $agenda = $this->tugas->agenda_id ? Agenda::find($this->tugas->agenda_id) : null;

        $penugasanQuery = Penugasan::query()
            ->where('untuk_type', 'tugas')
            ->where('untuk_id', $this->tugas->id)
            ->where('status', '!=', 'batal');

        if (! Auth::user()->can('kelola_tugas')) {
            $penugasanQuery->where(fn ($query) => $query
                ->where('user_id', Auth::id())
                ->orWhere('pembimbing_id', Auth::id()));
        }

        $penugasanIds = $penugasanQuery->pluck('id');
        $catatanPembimbing = CatatanPembimbing::whereIn('penugasan_id', $penugasanIds)->latest()->get();

        return view('livewire.kerjakan-tugas', [
            'agenda' => $agenda,
            'catatanPembimbing' => $catatanPembimbing,
            'namaPembimbing' => User::whereIn('id', $catatanPembimbing->pluck('oleh_id'))->pluck('nama', 'id'),
            'bolehMemberiCatatan' => Auth::user()->can('kelola_tugas') || Penugasan::query()
                ->whereIn('id', $penugasanIds)
                ->where('pembimbing_id', Auth::id())
                ->exists(),
        ]);
    }

    private function authorizeWork(): void
    {
        $this->tugas = Tugas::with(['bahan', 'komentar'])->findOrFail($this->tugas->id);
        Gate::authorize('kerjakan-tugas', $this->tugas);
    }
}
