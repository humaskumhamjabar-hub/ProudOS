<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Agenda\Models\Agenda;
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

    public function mount(int $tugasId, KonfirmasiPenugasan $konfirmasi): void
    {
        $this->tugas = Tugas::with(['bahan', 'komentar'])->findOrFail($tugasId);

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
        if ($this->tugas->status === 'baru') {
            $this->tugas->update(['status' => 'dikerjakan']);
        }
    }

    public function tandaiSelesai(): void
    {
        $this->tugas->update(['status' => 'selesai']);
    }

    public function unggahBahan(): void
    {
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
        $this->validate(['komentarBaru' => 'required|string|max:2000']);

        $this->tugas->komentar()->create([
            'user_id' => Auth::id(),
            'isi' => $this->komentarBaru,
        ]);

        $this->komentarBaru = '';
        $this->tugas->refresh();
    }

    public function render()
    {
        // Lapisan baca boleh melihat modul agenda untuk konteks kegiatan.
        $agenda = $this->tugas->agenda_id ? Agenda::find($this->tugas->agenda_id) : null;

        return view('livewire.kerjakan-tugas', ['agenda' => $agenda]);
    }
}
