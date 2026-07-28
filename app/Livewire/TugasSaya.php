<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Content\Models\PaketKonten;
use Modules\Scheduling\Models\Penugasan;
use Modules\Work\Models\Tugas;

#[Layout('components.layouts.app')]
class TugasSaya extends Component
{
    public function render()
    {
        $penugasan = Penugasan::with('peran')
            ->where('user_id', Auth::id())
            ->aktif()
            ->orderByRaw('coalesce(mulai_at, deadline_at)')
            ->get();

        // Lapisan baca boleh melihat banyak modul sekaligus — ambil judul tugas
        // untuk penugasan yang menunjuk ke tugas.
        $tugasIds = $penugasan->where('untuk_type', 'tugas')->pluck('untuk_id');
        $tugas = Tugas::whereIn('id', $tugasIds)->get()->keyBy('id');
        $paketLangsungIds = $penugasan->where('untuk_type', 'paket_konten')->pluck('untuk_id');
        $paketSubjekIds = $tugas->where('subjek_type', 'paket_konten')->pluck('subjek_id');
        $paket = PaketKonten::whereIn('id', $paketLangsungIds->merge($paketSubjekIds)->filter()->unique())->get()->keyBy('id');

        return view('livewire.tugas-saya', [
            'penugasan' => $penugasan,
            'tugas' => $tugas,
            'paket' => $paket,
        ]);
    }
}
