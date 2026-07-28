<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\People\Contracts\PenyediaStatusOrang;
use Modules\Scheduling\Actions\KonfirmasiPenugasan;
use Modules\Scheduling\Models\Penugasan;

#[Layout('components.layouts.app')]
class Beranda extends Component
{
    public function mount(KonfirmasiPenugasan $konfirmasi): void
    {
        // Membuka Beranda = melihat penugasan hari ini → dibaca_at terisi otomatis.
        Penugasan::query()
            ->where('user_id', Auth::id())
            ->aktif()
            ->untukHari(today())
            ->whereNull('dibaca_at')
            ->get()
            ->each(fn (Penugasan $p) => $konfirmasi->tandaiDibaca($p));
    }

    public function terima(int $penugasanId, KonfirmasiPenugasan $konfirmasi): void
    {
        $penugasan = Penugasan::where('user_id', Auth::id())->findOrFail($penugasanId);
        $konfirmasi->terima($penugasan);
    }

    public function render(PenyediaStatusOrang $orang)
    {
        $userId = Auth::id();

        $berjam = Penugasan::with('peran')
            ->where('user_id', $userId)->aktif()
            ->where('tipe', 'berjam')->whereDate('mulai_at', today())
            ->orderBy('mulai_at')->get();

        $berdeadline = Penugasan::with('peran')
            ->where('user_id', $userId)->aktif()
            ->where('tipe', 'berdeadline')
            ->orderBy('deadline_at')->get();

        $butuhTindakan = collect();
        if (Auth::user()->can('kelola_penugasan')) {
            // Paling atas layar koordinator: yang belum dikonfirmasi + yang butuh pengganti.
            $butuhTindakan = Penugasan::with('peran')
                ->where(fn ($q) => $q->belumDikonfirmasi()->orWhere('status', 'butuh_pengganti'))
                ->orderBy('mulai_at')->orderBy('deadline_at')
                ->get()
                ->map(fn (Penugasan $p) => [
                    'penugasan' => $p,
                    'orang' => $orang->ringkasan($p->user_id),
                ]);
        }

        return view('livewire.beranda', [
            'berjam' => $berjam,
            'berdeadline' => $berdeadline,
            'butuhTindakan' => $butuhTindakan,
        ]);
    }
}
