<?php

namespace App\Livewire;

use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\User;
use Modules\Scheduling\Models\Penugasan;

/**
 * Papan adalah read-layer lintas modul. Satu-satunya sumber posisi kartu
 * adalah paket_konten.status; penugasan hanya memperkaya PIC dan tenggat.
 */
#[Layout('components.layouts.app')]
class PapanKanban extends Component
{
    public string $filterOrang = '';

    public string $filterSumber = '';

    public function render()
    {
        $penugasan = Penugasan::with('peran')
            ->where('untuk_type', 'paket_konten')
            ->whereIn('status', ['aktif', 'butuh_pengganti'])
            ->get();
        $paketIdsOrang = $this->filterOrang !== ''
            ? $penugasan->where('user_id', (int) $this->filterOrang)->pluck('untuk_id')->unique()
            : collect();

        $paket = PaketKonten::query()
            ->whereIn('status', ['on_progress', 'finish_production', 'review'])
            ->whereIn('id', $penugasan->pluck('untuk_id')->unique())
            ->when($this->filterOrang !== '', fn ($query) => $query->whereIn('id', $paketIdsOrang))
            ->when($this->filterSumber === 'agenda', fn ($query) => $query->whereNotNull('agenda_id'))
            ->when($this->filterSumber === 'pr_plan', fn ($query) => $query->whereNotNull('pr_plan_item_id'))
            ->when($this->filterSumber === 'manual', fn ($query) => $query->whereNull('agenda_id')->whereNull('pr_plan_item_id'))
            ->latest('updated_at')
            ->get();

        $penugasanPerPaket = $penugasan->whereIn('untuk_id', $paket->pluck('id'))->groupBy('untuk_id');
        $orang = User::withTrashed()
            ->whereIn('id', $penugasan->pluck('user_id')->unique())
            ->orderBy('nama')
            ->get()
            ->keyBy('id');
        $kartu = $paket->map(fn (PaketKonten $item) => $this->buatKartu($item, $penugasanPerPaket->get($item->id, collect()), $orang));
        $kolom = collect(['on_progress', 'finish_production', 'review'])
            ->mapWithKeys(fn (string $status) => [$status => $kartu->where('status', $status)->values()])
            ->all();

        return view('livewire.papan-kanban', [
            'kolom' => $kolom,
            'orangFilter' => $orang,
            'totalAktif' => $paket->count(),
        ]);
    }

    private function buatKartu(PaketKonten $paket, Collection $penugasan, Collection $orang): array
    {
        $pic = $penugasan
            ->map(fn (Penugasan $item) => [
                'nama' => $orang->get($item->user_id)?->nama ?? 'Akun tidak tersedia',
                'peran' => $item->peran?->nama,
                'butuh_pengganti' => $item->status === 'butuh_pengganti',
            ])
            ->unique('nama')
            ->values();
        $tenggat = $penugasan->pluck('deadline_at')->filter()->sort()->first();

        return [
            'id' => $paket->id,
            'judul' => $paket->judul,
            'subjudul' => $paket->subjudul,
            'status' => $paket->status,
            'revisi_ke' => $paket->revisi_ke,
            'sumber' => $paket->pr_plan_item_id ? 'PR Plan' : ($paket->agenda_id ? 'Agenda' : 'Manual'),
            'pic' => $pic,
            'tenggat' => $tenggat,
            'updated_at' => $paket->updated_at,
        ];
    }
}
