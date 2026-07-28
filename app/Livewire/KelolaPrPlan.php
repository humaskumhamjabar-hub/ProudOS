<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Agenda\Models\Agenda;
use Modules\Content\Models\PaketKonten;
use Modules\Planning\Models\JenisOutput;
use Modules\Planning\Models\PrPlan;
use Modules\Planning\Models\PrPlanItem;
use Modules\Publishing\Models\Kanal;

#[Layout('components.layouts.app')]
class KelolaPrPlan extends Component
{
    public ?int $planId = null;

    public ?int $planAktifId = null;

    public bool $formPlanTerbuka = false;

    public string $nama = '';

    public string $tema = '';

    public string $periodeMulai = '';

    public string $periodeSelesai = '';

    public int|string $targetJumlahKonten = 8;

    public string $statusPlan = 'draf';

    public ?int $itemId = null;

    public bool $formItemTerbuka = false;

    public string $judulItem = '';

    public string $catatanItem = '';

    public string $rencanaKasar = '';

    public int|string $jenisOutputId = '';

    public array $kanalTujuan = [];

    public string $statusItem = 'ide';

    public ?int $itemJadwalId = null;

    public bool $formJadwalTerbuka = false;

    public string $agendaMulaiAt = '';

    public string $agendaSelesaiAt = '';

    public string $agendaLokasi = '';

    public function mount(): void
    {
        Gate::authorize('kelola_pr_plan');
        $this->planAktifId = PrPlan::query()
            ->orderByRaw("case status when 'berjalan' then 0 when 'draf' then 1 else 2 end")
            ->latest('periode_mulai')
            ->value('id');
    }

    public function pilihPlan(int $planId): void
    {
        Gate::authorize('kelola_pr_plan');
        PrPlan::findOrFail($planId);
        $this->planAktifId = $planId;
        $this->tutupSemuaForm();
    }

    public function buatPlan(): void
    {
        Gate::authorize('kelola_pr_plan');
        $this->resetFormPlan();
        $this->periodeMulai = now()->startOfMonth()->format('Y-m-d');
        $this->periodeSelesai = now()->endOfMonth()->format('Y-m-d');
        $this->formPlanTerbuka = true;
    }

    public function editPlan(int $planId): void
    {
        Gate::authorize('kelola_pr_plan');
        $plan = PrPlan::findOrFail($planId);

        $this->resetFormPlan();
        $this->planId = $plan->id;
        $this->nama = $plan->nama;
        $this->tema = $plan->tema ?? '';
        $this->periodeMulai = $plan->periode_mulai->format('Y-m-d');
        $this->periodeSelesai = $plan->periode_selesai->format('Y-m-d');
        $this->targetJumlahKonten = $plan->target_jumlah_konten;
        $this->statusPlan = $plan->status;
        $this->formPlanTerbuka = true;
    }

    public function simpanPlan(): void
    {
        Gate::authorize('kelola_pr_plan');
        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tema' => ['nullable', 'string', 'max:255'],
            'periodeMulai' => ['required', 'date'],
            'periodeSelesai' => ['required', 'date', 'after_or_equal:periodeMulai'],
            'targetJumlahKonten' => ['required', 'integer', 'min:1', 'max:10000'],
            'statusPlan' => ['required', Rule::in(['draf', 'berjalan', 'selesai'])],
        ], [], [
            'periodeMulai' => 'awal periode',
            'periodeSelesai' => 'akhir periode',
            'targetJumlahKonten' => 'target konten',
        ]);

        $atribut = [
            'nama' => $data['nama'],
            'tema' => $data['tema'] ?: null,
            'periode_mulai' => $data['periodeMulai'],
            'periode_selesai' => $data['periodeSelesai'],
            'target_jumlah_konten' => $data['targetJumlahKonten'],
            'status' => $data['statusPlan'],
        ];

        if ($this->planId) {
            $plan = PrPlan::findOrFail($this->planId);
            $plan->update($atribut);
            $pesan = 'PR Plan berhasil diperbarui.';
        } else {
            $plan = PrPlan::create($atribut + ['dibuat_oleh' => Auth::id()]);
            $pesan = 'PR Plan berhasil dibuat.';
        }

        $this->planAktifId = $plan->id;
        $this->resetFormPlan();
        session()->flash('pr-plan-tersimpan', $pesan);
    }

    public function tambahItem(int $planId): void
    {
        Gate::authorize('kelola_pr_plan');
        PrPlan::findOrFail($planId);
        $this->resetFormItem();
        $this->planAktifId = $planId;
        $this->formItemTerbuka = true;
    }

    public function editItem(int $itemId): void
    {
        Gate::authorize('kelola_pr_plan');
        $item = PrPlanItem::findOrFail($itemId);

        $this->resetFormItem();
        $this->planAktifId = $item->pr_plan_id;
        $this->itemId = $item->id;
        $this->judulItem = $item->judul;
        $this->catatanItem = $item->catatan ?? '';
        $this->rencanaKasar = $item->rencana_kasar ?? '';
        $this->jenisOutputId = $item->jenis_output_id;
        $this->kanalTujuan = $item->kanal_tujuan ?? [];
        $this->statusItem = $item->status;
        $this->formItemTerbuka = true;
    }

    public function simpanItem(): void
    {
        Gate::authorize('kelola_pr_plan');
        $plan = PrPlan::findOrFail($this->planAktifId);
        $data = $this->validate([
            'judulItem' => ['required', 'string', 'max:255'],
            'catatanItem' => ['nullable', 'string', 'max:5000'],
            'rencanaKasar' => ['nullable', 'string', 'max:255'],
            'jenisOutputId' => ['required', 'integer', Rule::exists('jenis_outputs', 'id')->where('aktif', true)],
            'kanalTujuan' => ['array'],
            'kanalTujuan.*' => ['integer', 'distinct', Rule::exists('kanal', 'id')->where('aktif', true)],
            'statusItem' => ['required', Rule::in(['ide', 'dijadwalkan', 'diproduksi', 'batal'])],
        ], [], [
            'judulItem' => 'judul konten',
            'rencanaKasar' => 'rencana kasar',
            'jenisOutputId' => 'jenis output',
            'kanalTujuan' => 'kanal tujuan',
        ]);

        $atribut = [
            'judul' => $data['judulItem'],
            'catatan' => $data['catatanItem'] ?: null,
            'rencana_kasar' => $data['rencanaKasar'] ?: null,
            'jenis_output_id' => $data['jenisOutputId'],
            'kanal_tujuan' => array_values(array_map('intval', $data['kanalTujuan'])),
            'status' => $data['statusItem'],
        ];

        if ($this->itemId) {
            $item = PrPlanItem::where('pr_plan_id', $plan->id)->findOrFail($this->itemId);
            if ($item->agenda_id && $atribut['status'] === 'ide') {
                $atribut['status'] = 'dijadwalkan';
            }
            $item->update($atribut);
            $pesan = 'Item konten berhasil diperbarui.';
        } else {
            $plan->items()->create($atribut);
            $pesan = 'Item konten masuk antrean PR Plan.';
        }

        $this->resetFormItem();
        session()->flash('pr-plan-tersimpan', $pesan);
    }

    public function bukaJadwal(int $itemId): void
    {
        Gate::authorize('kelola_pr_plan');
        $item = PrPlanItem::findOrFail($itemId);

        $this->resetFormJadwal();
        $this->planAktifId = $item->pr_plan_id;
        $this->itemJadwalId = $item->id;
        $this->agendaMulaiAt = now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
        $this->formJadwalTerbuka = true;
    }

    public function jadwalkanItem(): void
    {
        Gate::authorize('kelola_pr_plan');
        $item = PrPlanItem::findOrFail($this->itemJadwalId);
        abort_if($item->agenda_id !== null, 409);

        $data = $this->validate([
            'agendaMulaiAt' => ['required', 'date'],
            'agendaSelesaiAt' => ['nullable', 'date', 'after:agendaMulaiAt'],
            'agendaLokasi' => ['nullable', 'string', 'max:255'],
        ], [], [
            'agendaMulaiAt' => 'waktu mulai',
            'agendaSelesaiAt' => 'waktu selesai',
            'agendaLokasi' => 'lokasi',
        ]);

        DB::transaction(function () use ($item, $data) {
            $agenda = Agenda::create([
                'judul' => $item->judul,
                'deskripsi' => $item->catatan,
                'mulai_at' => $data['agendaMulaiAt'],
                'selesai_at' => $data['agendaSelesaiAt'] ?: null,
                'lokasi' => $data['agendaLokasi'] ?: null,
                'sumber_type' => 'pr_plan_item',
                'sumber_id' => $item->id,
                'status' => 'rencana',
                'dibuat_oleh' => Auth::id(),
            ]);

            $item->update(['agenda_id' => $agenda->id, 'status' => 'dijadwalkan']);
        });

        $this->resetFormJadwal();
        session()->flash('pr-plan-tersimpan', 'Item dijadwalkan. Tanggal resminya kini tersimpan di Agenda.');
    }

    public function mulaiProduksi(int $itemId): void
    {
        Gate::authorize('kelola_konten');
        $item = PrPlanItem::findOrFail($itemId);
        abort_if($item->agenda_id === null || $item->status === 'batal', 422);

        DB::transaction(function () use ($item) {
            $paket = PaketKonten::firstOrCreate(
                ['pr_plan_item_id' => $item->id],
                [
                    'agenda_id' => $item->agenda_id,
                    'judul' => $item->judul,
                    'status' => 'on_progress',
                    'revisi_ke' => 0,
                    'dibuat_oleh' => Auth::id(),
                ],
            );

            if ($item->status !== 'diproduksi') {
                $item->update(['status' => 'diproduksi']);
            }

        });

        $this->redirectRoute('produksi.index');
    }

    public function tutupSemuaForm(): void
    {
        $this->resetFormPlan();
        $this->resetFormItem();
        $this->resetFormJadwal();
    }

    private function resetFormPlan(): void
    {
        $this->reset(['planId', 'formPlanTerbuka', 'nama', 'tema', 'periodeMulai', 'periodeSelesai']);
        $this->targetJumlahKonten = 8;
        $this->statusPlan = 'draf';
        $this->resetValidation();
    }

    private function resetFormItem(): void
    {
        $this->reset([
            'itemId', 'formItemTerbuka', 'judulItem', 'catatanItem',
            'rencanaKasar', 'jenisOutputId', 'kanalTujuan',
        ]);
        $this->statusItem = 'ide';
        $this->resetValidation();
    }

    private function resetFormJadwal(): void
    {
        $this->reset(['itemJadwalId', 'formJadwalTerbuka', 'agendaMulaiAt', 'agendaSelesaiAt', 'agendaLokasi']);
        $this->resetValidation();
    }

    public function render()
    {
        $plans = PrPlan::withCount('items')
            ->orderByDesc('periode_mulai')
            ->get();

        $planAktif = $this->planAktifId
            ? PrPlan::with(['items.jenisOutput'])->find($this->planAktifId)
            : null;

        $kanal = Kanal::query()->where('aktif', true)->orderBy('nama')->get();
        $namaKanal = $kanal->pluck('nama', 'id');

        return view('livewire.kelola-pr-plan', [
            'plans' => $plans,
            'planAktif' => $planAktif,
            'jenisOutput' => JenisOutput::query()->where('aktif', true)->orderBy('nama')->get(),
            'kanal' => $kanal,
            'namaKanal' => $namaKanal,
        ]);
    }
}
