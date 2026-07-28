<?php

namespace App\Livewire;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Agenda\Models\Agenda;

#[Layout('components.layouts.app')]
class Kalender extends Component
{
    public string $bulan; // format Y-m

    public function mount(): void
    {
        $this->bulan = now()->format('Y-m');
    }

    public function gantiBulan(int $arah): void
    {
        $this->bulan = Carbon::parse($this->bulan.'-01')->addMonths($arah)->format('Y-m');
    }

    public function render()
    {
        $awal = Carbon::parse($this->bulan.'-01')->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        $agendaPerTanggal = Agenda::query()
            ->whereBetween('mulai_at', [$awal, $akhir->copy()->endOfDay()])
            ->orderBy('mulai_at')
            ->get()
            ->groupBy(fn (Agenda $a) => $a->mulai_at->format('Y-m-d'));

        return view('livewire.kalender', [
            'awal' => $awal,
            'akhir' => $akhir,
            'agendaPerTanggal' => $agendaPerTanggal,
        ]);
    }
}
