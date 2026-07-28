<?php

namespace App\Livewire;

use App\Jobs\EkstrakTeksBahan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Modules\Agenda\Models\Agenda;
use Modules\Ai\Actions\BuatUsulan;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Content\Models\AiUsulan;
use Modules\Content\Models\Bahan;
use Modules\Content\Models\Draf;
use Modules\Content\Models\PaketKonten;
use Modules\Planning\Models\PrPlanItem;

#[Layout('components.layouts.app')]
class KelolaProduksiKonten extends Component
{
    use WithFileUploads;

    public ?int $paketAktifId = null;

    public string $jenisDraf = 'berita';

    public string $isiDraf = '';

    public string $tipeBahan = 'foto';

    public array $unggahanBahan = [];

    public string $catatanBahan = '';

    public string $jenisUsulanAi = 'fakta';

    public ?int $usulanAiDieditId = null;

    public string $isiEditUsulanAi = '';

    public function mount(?int $paket = null): void
    {
        Gate::authorize('kelola_konten');
        $this->paketAktifId = $paket
            ? PaketKonten::findOrFail($paket)->id
            : PaketKonten::query()
                ->where('status', '!=', 'arsip')
                ->latest('updated_at')
                ->value('id');
    }

    public function mulaiDariPrPlan(int $itemId): void
    {
        Gate::authorize('kelola_konten');
        $item = PrPlanItem::findOrFail($itemId);
        abort_if($item->agenda_id === null || $item->status === 'batal', 422);

        $paket = DB::transaction(function () use ($item) {
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

            return $paket;
        });

        $this->pilihPaket($paket->id);
        session()->flash('produksi-tersimpan', 'Paket masuk meja produksi dan siap dikerjakan.');
    }

    public function pilihPaket(int $paketId): void
    {
        Gate::authorize('kelola_konten');
        $paket = PaketKonten::findOrFail($paketId);

        $this->paketAktifId = $paket->id;
        $drafTerbaru = $paket->draf()->latest('versi')->first();
        $this->jenisDraf = $drafTerbaru?->jenis ?? 'berita';
        $this->isiDraf = $drafTerbaru?->isi ?? '';
        $this->reset(['usulanAiDieditId', 'isiEditUsulanAi']);
        $this->resetValidation();
    }

    public function buatUsulanAi(PenyediaAi $penyedia, BuatUsulan $pembuat): void
    {
        Gate::authorize('kelola_konten');
        $paket = PaketKonten::findOrFail($this->paketAktifId);
        $data = $this->validate([
            'jenisUsulanAi' => ['required', Rule::in(['fakta', 'berita', 'caption', 'opsi_judul', 'ringkasan'])],
        ]);
        $sumber = $paket->bahan()
            ->where('status_ekstraksi', 'selesai')
            ->whereNotNull('teks_terekstrak')
            ->orderBy('urutan')
            ->pluck('teks_terekstrak')
            ->map(fn (string $teks) => trim($teks))
            ->filter()
            ->values()
            ->all();

        if ($sumber === []) {
            $this->addError('ai', 'Tambahkan catatan atau dokumen dengan teks siap sebelum meminta usulan AI.');

            return;
        }

        if (! $penyedia->tersedia()) {
            $this->addError('ai', 'Penyedia AI belum dikonfigurasi di server.');

            return;
        }

        try {
            $hasil = $pembuat->handle($data['jenisUsulanAi'], $paket->judul, $sumber);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('ai', 'Usulan AI gagal dibuat. Sumber dan draf manusia tetap aman; silakan coba lagi.');

            return;
        }

        if (trim($hasil->isi) === '') {
            $this->addError('ai', 'Penyedia AI mengembalikan hasil kosong. Silakan coba lagi.');

            return;
        }

        $paket->aiUsulan()->create([
            'jenis' => $data['jenisUsulanAi'],
            'isi' => trim($hasil->isi),
            'status' => 'menunggu',
            'model' => $hasil->model,
            'prompt_versi' => $hasil->promptVersi,
        ]);

        $paket->touch();
        session()->flash('produksi-tersimpan', 'Usulan AI selesai dibuat dan menunggu keputusan manusia.');
    }

    public function terimaUsulanAi(int $usulanId): void
    {
        $this->tinjauUsulanAi($usulanId, 'diterima');
    }

    public function tolakUsulanAi(int $usulanId): void
    {
        $this->tinjauUsulanAi($usulanId, 'ditolak');
    }

    public function mulaiEditUsulanAi(int $usulanId): void
    {
        Gate::authorize('kelola_konten');
        $usulan = $this->usulanPaketAktif($usulanId);
        abort_unless($usulan->status === 'menunggu', 422);

        $this->usulanAiDieditId = $usulan->id;
        $this->isiEditUsulanAi = $usulan->isi;
        $this->resetValidation('isiEditUsulanAi');
    }

    public function batalEditUsulanAi(): void
    {
        $this->reset(['usulanAiDieditId', 'isiEditUsulanAi']);
        $this->resetValidation('isiEditUsulanAi');
    }

    public function simpanEditUsulanAi(): void
    {
        Gate::authorize('kelola_konten');
        $usulan = $this->usulanPaketAktif((int) $this->usulanAiDieditId);
        abort_unless($usulan->status === 'menunggu', 422);
        $data = $this->validate([
            'isiEditUsulanAi' => ['required', 'string', 'max:100000'],
        ], [], ['isiEditUsulanAi' => 'isi usulan']);

        $usulan->update([
            'isi' => $data['isiEditUsulanAi'],
            'status' => 'diedit',
            'ditinjau_oleh' => Auth::id(),
            'ditinjau_at' => now(),
        ]);

        $this->reset(['usulanAiDieditId', 'isiEditUsulanAi']);
        session()->flash('produksi-tersimpan', 'Koreksi manusia tersimpan di jejak usulan AI.');
    }

    public function gunakanUsulanAi(int $usulanId): void
    {
        Gate::authorize('kelola_konten');
        $usulan = $this->usulanPaketAktif($usulanId);
        abort_unless(in_array($usulan->status, ['diterima', 'diedit'], true), 422);

        $this->jenisDraf = match ($usulan->jenis) {
            'caption' => 'caption',
            'opsi_judul' => 'judul',
            default => 'berita',
        };
        $this->isiDraf = $usulan->isi;
        $this->resetValidation(['jenisDraf', 'isiDraf']);
        session()->flash('produksi-tersimpan', 'Usulan disalin ke editor. Periksa lagi lalu simpan sebagai versi manusia.');
    }

    private function tinjauUsulanAi(int $usulanId, string $status): void
    {
        Gate::authorize('kelola_konten');
        abort_unless(in_array($status, ['diterima', 'ditolak'], true), 422);
        $usulan = $this->usulanPaketAktif($usulanId);
        abort_unless($usulan->status === 'menunggu', 422);

        $usulan->update([
            'status' => $status,
            'ditinjau_oleh' => Auth::id(),
            'ditinjau_at' => now(),
        ]);
        session()->flash('produksi-tersimpan', $status === 'diterima' ? 'Usulan diterima. Salin ke editor saat siap.' : 'Usulan ditolak dan tetap tersimpan sebagai jejak.');
    }

    private function usulanPaketAktif(int $usulanId): AiUsulan
    {
        return AiUsulan::where('paket_konten_id', $this->paketAktifId)->findOrFail($usulanId);
    }

    public function simpanDraf(): void
    {
        Gate::authorize('kelola_konten');
        $paket = PaketKonten::findOrFail($this->paketAktifId);
        $data = $this->validate([
            'jenisDraf' => ['required', Rule::in(['berita', 'caption', 'judul', 'script'])],
            'isiDraf' => ['required', 'string', 'max:100000'],
        ], [], [
            'jenisDraf' => 'jenis draf',
            'isiDraf' => 'isi draf',
        ]);

        $versiBerikutnya = (int) $paket->draf()
            ->where('jenis', $data['jenisDraf'])
            ->max('versi') + 1;

        $paket->draf()->create([
            'jenis' => $data['jenisDraf'],
            'isi' => $data['isiDraf'],
            'versi' => $versiBerikutnya,
            'asal' => 'manusia',
            'latihan' => false,
            'dibuat_oleh' => Auth::id(),
        ]);

        $paket->touch();
        session()->flash('produksi-tersimpan', "Draf versi {$versiBerikutnya} tersimpan. Versi lama tetap aman.");
    }

    public function muatDraf(int $drafId): void
    {
        Gate::authorize('kelola_konten');
        $draf = Draf::where('paket_konten_id', $this->paketAktifId)->findOrFail($drafId);

        $this->jenisDraf = $draf->jenis;
        $this->isiDraf = $draf->isi;
        $this->resetValidation();
    }

    public function unggahBahan(): void
    {
        Gate::authorize('kelola_konten');
        $paket = PaketKonten::findOrFail($this->paketAktifId);
        abort_if($paket->status === 'arsip', 422);

        $data = $this->validate([
            'tipeBahan' => ['required', Rule::in(['foto', 'dokumen', 'catatan', 'audio'])],
            'catatanBahan' => [Rule::requiredIf($this->tipeBahan === 'catatan'), 'nullable', 'string', 'max:20000'],
            'unggahanBahan' => [Rule::requiredIf($this->tipeBahan !== 'catatan'), 'array', 'max:20'],
            'unggahanBahan.*' => ['file', 'max:20480', $this->aturanMimeBahan()],
        ], [], [
            'tipeBahan' => 'tipe bahan',
            'catatanBahan' => 'isi catatan',
            'unggahanBahan' => 'file bahan',
            'unggahanBahan.*' => 'file bahan',
        ]);

        $urutan = (int) $paket->bahan()->max('urutan');

        if ($data['tipeBahan'] === 'catatan') {
            $paket->bahan()->create([
                'tipe' => 'catatan',
                'path' => '',
                'nama_asli' => 'Catatan produksi',
                'mime' => 'text/plain',
                'teks_terekstrak' => $data['catatanBahan'],
                'status_ekstraksi' => 'selesai',
                'dipakai_final' => false,
                'diunggah_oleh' => Auth::id(),
                'urutan' => $urutan + 1,
            ]);
        } else {
            foreach ($data['unggahanBahan'] as $unggahan) {
                $path = $unggahan->store("bahan/{$paket->id}", 'local');
                $bahan = $paket->bahan()->create([
                    'tipe' => $data['tipeBahan'],
                    'path' => $path,
                    'nama_asli' => $unggahan->getClientOriginalName(),
                    'mime' => $unggahan->getMimeType(),
                    'status_ekstraksi' => 'menunggu',
                    'dipakai_final' => false,
                    'diunggah_oleh' => Auth::id(),
                    'urutan' => ++$urutan,
                ]);

                if ($bahan->tipe === 'dokumen') {
                    EkstrakTeksBahan::dispatch($bahan->id);
                }
            }
        }

        $paket->touch();
        $this->reset(['unggahanBahan', 'catatanBahan']);
        session()->flash('produksi-tersimpan', 'Bahan produksi berhasil ditambahkan.');
    }

    public function toggleDipakaiFinal(int $bahanId): void
    {
        Gate::authorize('kelola_konten');
        $bahan = $this->bahanPaketAktif($bahanId);
        abort_unless($bahan->tipe === 'foto', 422);

        $bahan->update(['dipakai_final' => ! $bahan->dipakai_final]);
    }

    public function cobaUlangEkstraksi(int $bahanId): void
    {
        Gate::authorize('kelola_konten');
        $bahan = $this->bahanPaketAktif($bahanId);
        abort_unless($bahan->tipe === 'dokumen', 422);

        $bahan->update(['status_ekstraksi' => 'menunggu']);
        EkstrakTeksBahan::dispatch($bahan->id);
        session()->flash('produksi-tersimpan', 'Ekstraksi teks dimasukkan kembali ke antrean.');
    }

    public function urutkanBahan(array $urutanIds): void
    {
        Gate::authorize('kelola_konten');
        $paket = PaketKonten::findOrFail($this->paketAktifId);
        $idsTersimpan = $paket->bahan()->orderBy('urutan')->pluck('id')->all();
        $idsDikirim = array_values(array_map('intval', $urutanIds));
        abort_unless(count($idsDikirim) === count(array_unique($idsDikirim)), 422);
        $idsTersimpanTerurut = $idsTersimpan;
        $idsDikirimTerurut = $idsDikirim;
        sort($idsTersimpanTerurut);
        sort($idsDikirimTerurut);
        abort_unless($idsTersimpanTerurut === $idsDikirimTerurut, 422);

        DB::transaction(function () use ($paket, $idsDikirim) {
            foreach ($idsDikirim as $index => $bahanId) {
                $paket->bahan()->whereKey($bahanId)->update(['urutan' => $index + 1]);
            }
        });
    }

    public function pindahBahan(int $bahanId, string $arah): void
    {
        Gate::authorize('kelola_konten');
        abort_unless(in_array($arah, ['naik', 'turun'], true), 422);
        $bahan = $this->bahanPaketAktif($bahanId);
        $query = PaketKonten::findOrFail($this->paketAktifId)->bahan();
        $tetangga = $arah === 'naik'
            ? (clone $query)->where('urutan', '<', $bahan->urutan)->orderByDesc('urutan')->first()
            : (clone $query)->where('urutan', '>', $bahan->urutan)->orderBy('urutan')->first();

        if (! $tetangga) {
            return;
        }

        DB::transaction(function () use ($bahan, $tetangga) {
            $urutanBahan = $bahan->urutan;
            $bahan->update(['urutan' => $tetangga->urutan]);
            $tetangga->update(['urutan' => $urutanBahan]);
        });
    }

    public function hapusBahan(int $bahanId): void
    {
        Gate::authorize('kelola_konten');
        $bahan = $this->bahanPaketAktif($bahanId);
        abort_if(PaketKonten::findOrFail($this->paketAktifId)->status === 'arsip', 422);

        DB::transaction(function () use ($bahan) {
            $path = $bahan->path;
            $bahan->delete();

            if ($path !== '') {
                Storage::disk('local')->delete($path);
            }
        });

        $this->rapikanUrutanBahan();
        session()->flash('produksi-tersimpan', 'Bahan produksi dihapus.');
    }

    private function aturanMimeBahan(): string
    {
        return match ($this->tipeBahan) {
            'foto' => 'mimes:jpg,jpeg,png,webp',
            'dokumen' => 'mimes:pdf,doc,docx,txt',
            'audio' => 'mimes:mp3,wav,m4a,ogg',
            default => 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,txt,mp3,wav,m4a,ogg',
        };
    }

    private function bahanPaketAktif(int $bahanId): Bahan
    {
        return Bahan::where('paket_konten_id', $this->paketAktifId)->findOrFail($bahanId);
    }

    private function rapikanUrutanBahan(): void
    {
        PaketKonten::findOrFail($this->paketAktifId)
            ->bahan()
            ->orderBy('urutan')
            ->get()
            ->each(fn (Bahan $bahan, int $index) => $bahan->update(['urutan' => $index + 1]));
    }

    public function ubahStatus(int $paketId, string $status): void
    {
        Gate::authorize('kelola_konten');
        $paket = PaketKonten::findOrFail($paketId);
        $transisi = [
            'on_progress' => 'finish_production',
            'finish_production' => 'review',
        ];

        abort_unless(($transisi[$paket->status] ?? null) === $status, 422);
        $paket->update(['status' => $status]);
        session()->flash('produksi-tersimpan', 'Status produksi berhasil diperbarui.');
    }

    public function kembalikanRevisi(int $paketId): void
    {
        Gate::authorize('kelola_konten');
        $paket = PaketKonten::findOrFail($paketId);
        abort_unless(in_array($paket->status, ['finish_production', 'review'], true), 422);

        $paket->update([
            'status' => 'on_progress',
            'revisi_ke' => $paket->revisi_ke + 1,
        ]);
        session()->flash('produksi-tersimpan', "Paket dikembalikan sebagai revisi ke-{$paket->revisi_ke}.");
    }

    public function render()
    {
        $paket = PaketKonten::query()
            ->with([
                'draf' => fn ($query) => $query->latest('versi'),
                'bahan' => fn ($query) => $query->orderBy('urutan'),
                'aiUsulan' => fn ($query) => $query->latest(),
            ])
            ->where('status', '!=', 'arsip')
            ->latest('updated_at')
            ->get();

        $paketAktif = $this->paketAktifId
            ? $paket->firstWhere('id', $this->paketAktifId)
            : null;

        $itemSiap = PrPlanItem::query()
            ->with(['plan', 'jenisOutput'])
            ->whereNotNull('agenda_id')
            ->where('status', 'dijadwalkan')
            ->whereNotIn('id', PaketKonten::whereNotNull('pr_plan_item_id')->pluck('pr_plan_item_id'))
            ->latest('updated_at')
            ->get();

        $agenda = Agenda::query()
            ->whereIn('id', $itemSiap->pluck('agenda_id')->merge($paket->pluck('agenda_id'))->filter()->unique())
            ->get()
            ->keyBy('id');

        return view('livewire.kelola-produksi-konten', [
            'paket' => $paket,
            'paketAktif' => $paketAktif,
            'itemSiap' => $itemSiap,
            'agenda' => $agenda,
            'drafAktif' => $paketAktif?->draf ?? collect(),
            'bahanAktif' => $paketAktif?->bahan ?? collect(),
            'usulanAiAktif' => $paketAktif?->aiUsulan ?? collect(),
            'aiTersedia' => app(PenyediaAi::class)->tersedia(),
        ]);
    }
}
