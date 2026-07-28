<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Content\Models\CatatanPembimbing;
use Modules\Content\Models\Draf;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Batch;
use Modules\People\Models\User;
use Modules\Planning\Models\PrPlan;
use Modules\Publishing\Models\Kanal;
use Modules\Publishing\Models\Publikasi;
use Modules\Scheduling\Models\Penugasan;

#[Layout('components.layouts.app')]
class PusatLaporan extends Component
{
    public string $tab = 'publikasi';

    public string $mulai = '';

    public string $selesai = '';

    public ?int $kanalId = null;

    public ?int $prPlanId = null;

    public ?int $batchId = null;

    public function mount(): void
    {
        Gate::authorize('lihat_laporan');
        $this->mulai = now()->startOfMonth()->toDateString();
        $this->selesai = now()->endOfMonth()->toDateString();
        $this->prPlanId = PrPlan::latest('periode_mulai')->value('id');
        $this->batchId = Batch::latest('mulai')->value('id');
    }

    public function pilihTab(string $tab): void
    {
        Gate::authorize('lihat_laporan');
        abort_unless(in_array($tab, ['publikasi', 'pr-plan', 'magang'], true), 422);
        $this->tab = $tab;
    }

    public function unduhCsvPublikasi()
    {
        Gate::authorize('lihat_laporan');
        $publikasi = $this->queryPublikasi()->get();
        $nama = "laporan-publikasi-{$this->mulai}-{$this->selesai}.csv";

        return response()->streamDownload(function () use ($publikasi) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Tanggal tayang', 'Kanal', 'PIC', 'Judul paket', 'URL', 'Diubah setelah tayang']);

            foreach ($publikasi as $item) {
                fputcsv($file, [
                    $item->tayang_at->format('Y-m-d H:i'),
                    $item->kanal?->nama,
                    $item->pic_nama,
                    $item->judul_paket,
                    $item->url,
                    $item->diubah_setelah_tayang ? 'Ya' : 'Tidak',
                ]);
            }

            fclose($file);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function unduhCsvMagang()
    {
        Gate::authorize('lihat_laporan');
        $rekap = $this->rekapMagang();
        $batch = $this->batchId ? Batch::find($this->batchId) : null;
        $slug = str($batch?->nama ?? 'batch')->slug();

        return response()->streamDownload(function () use ($rekap) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Nama', 'Jumlah penugasan', 'Ragam kegiatan', 'Jumlah pembimbing', 'Karya latihan', 'Catatan pembimbing', 'Sebaran peran']);

            foreach ($rekap as $peserta) {
                $peran = collect($peserta['peran'])->map(fn (int $jumlah, string $nama) => "{$nama}: {$jumlah}")->implode('; ');
                fputcsv($file, [$peserta['nama'], $peserta['jumlah_penugasan'], $peserta['ragam_kegiatan'], $peserta['jumlah_pembimbing'], $peserta['karya_latihan'], $peserta['catatan_pembimbing'], $peran]);
            }

            fclose($file);
        }, "rekap-magang-{$slug}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function queryPublikasi(): Builder
    {
        return Publikasi::query()
            ->with('kanal')
            ->when($this->mulai, fn (Builder $query) => $query->whereDate('tayang_at', '>=', $this->mulai))
            ->when($this->selesai, fn (Builder $query) => $query->whereDate('tayang_at', '<=', $this->selesai))
            ->when($this->kanalId, fn (Builder $query) => $query->where('kanal_id', $this->kanalId))
            ->orderByDesc('tayang_at');
    }

    private function evaluasiPrPlan(): array
    {
        $plan = $this->prPlanId ? PrPlan::with('items')->find($this->prPlanId) : null;

        if (! $plan) {
            return ['plan' => null, 'target' => 0, 'realisasi' => 0, 'pipeline' => 0, 'belum_dikerjakan' => 0, 'batal' => 0, 'kekurangan' => 0, 'persentase' => 0.0];
        }

        $itemIds = $plan->items->pluck('id');
        $paket = PaketKonten::whereIn('pr_plan_item_id', $itemIds)->get();
        $paketTayangIds = Publikasi::whereIn('paket_konten_id', $paket->pluck('id'))->distinct()->pluck('paket_konten_id');
        $realisasiItemIds = $paket->whereIn('id', $paketTayangIds)->pluck('pr_plan_item_id')->unique();
        $pipelineItemIds = $paket->whereNotIn('id', $paketTayangIds)->pluck('pr_plan_item_id')->unique();
        $target = (int) $plan->target_jumlah_konten;
        $realisasi = $realisasiItemIds->count();

        return [
            'plan' => $plan,
            'target' => $target,
            'realisasi' => $realisasi,
            'pipeline' => $pipelineItemIds->count(),
            'belum_dikerjakan' => $plan->items->where('status', 'ide')->count(),
            'batal' => $plan->items->where('status', 'batal')->count(),
            'kekurangan' => max(0, $target - $realisasi),
            'persentase' => $target > 0 ? round(($realisasi / $target) * 100, 1) : 0.0,
        ];
    }

    private function rekapMagang(): Collection
    {
        if (! $this->batchId) {
            return collect();
        }

        $peserta = User::withTrashed()->where('batch_id', $this->batchId)->orderBy('nama')->get();
        $penugasan = Penugasan::with('peran')->whereIn('user_id', $peserta->pluck('id'))->get()->groupBy('user_id');
        $drafLatihan = Draf::where('latihan', true)->whereIn('dibuat_oleh', $peserta->pluck('id'))->get()->groupBy('dibuat_oleh');
        $catatan = CatatanPembimbing::whereIn('penugasan_id', $penugasan->flatten()->pluck('id'))->get()->groupBy('penugasan_id');

        return $peserta->map(function (User $user) use ($penugasan, $drafLatihan, $catatan) {
            $milik = $penugasan->get($user->id, collect());

            return [
                'id' => $user->id,
                'nama' => $user->nama,
                'jumlah_penugasan' => $milik->count(),
                'ragam_kegiatan' => $milik->map(fn (Penugasan $item) => $item->untuk_type.':'.$item->untuk_id)->unique()->count(),
                'jumlah_pembimbing' => $milik->pluck('pembimbing_id')->filter()->unique()->count(),
                'karya_latihan' => $drafLatihan->get($user->id, collect())->count(),
                'catatan_pembimbing' => $milik->sum(fn (Penugasan $item) => $catatan->get($item->id, collect())->count()),
                'peran' => $milik->countBy(fn (Penugasan $item) => $item->peran?->nama ?? 'Tanpa peran')->sortKeys()->all(),
            ];
        });
    }

    public function render()
    {
        Gate::authorize('lihat_laporan');
        $publikasi = $this->queryPublikasi()->get();
        $paketJudul = PaketKonten::whereIn('id', $publikasi->pluck('paket_konten_id')->filter())->pluck('judul', 'id');
        $picNama = User::withTrashed()->whereIn('id', $publikasi->pluck('pic_id'))->pluck('nama', 'id');
        $publikasi->each(function (Publikasi $item) use ($paketJudul, $picNama) {
            $item->setAttribute('judul_paket', $paketJudul[$item->paket_konten_id] ?? 'Publikasi mandiri');
            $item->setAttribute('pic_nama', $picNama[$item->pic_id] ?? 'Akun tidak tersedia');
        });
        $perKanal = $publikasi->countBy(fn (Publikasi $item) => $item->kanal?->nama ?? 'Tanpa kanal')->sortDesc();

        return view('livewire.pusat-laporan', [
            'publikasi' => $publikasi,
            'ringkasanPublikasi' => [
                'total' => $publikasi->count(),
                'kanal' => $perKanal->all(),
                'pic' => $publikasi->pluck('pic_id')->filter()->unique()->count(),
                'revisi_tayang' => $publikasi->where('diubah_setelah_tayang', true)->count(),
            ],
            'evaluasiPrPlan' => $this->evaluasiPrPlan(),
            'rekapMagang' => $this->rekapMagang(),
            'kanal' => Kanal::orderBy('nama')->get(),
            'prPlans' => PrPlan::latest('periode_mulai')->get(),
            'batches' => Batch::latest('mulai')->get(),
        ]);
    }
}
