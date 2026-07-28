<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Modules\Content\Models\PaketKonten;
use Modules\Publishing\Models\Kanal;
use Modules\Publishing\Models\Publikasi;

#[Layout('components.layouts.app')]
class KelolaPublikasi extends Component
{
    use WithFileUploads;

    public ?int $paketAktifId = null;

    public ?int $publikasiId = null;

    public int|string $kanalId = '';

    public string $tayangAt = '';

    public string $url = '';

    public $buktiTayang;

    public bool $diubahSetelahTayang = false;

    public string $alasanPerubahan = '';

    public string $dimintaOleh = '';

    public function mount(?int $paket = null): void
    {
        Gate::authorize('upload_publikasi');

        if ($paket) {
            $this->pilihPaket($paket);
        } else {
            $this->paketAktifId = PaketKonten::query()
                ->where('status', 'review')
                ->latest('updated_at')
                ->value('id');
            $this->tayangAt = now()->format('Y-m-d\TH:i');
        }
    }

    public function pilihPaket(int $paketId): void
    {
        Gate::authorize('upload_publikasi');
        $paket = PaketKonten::findOrFail($paketId);
        abort_unless(in_array($paket->status, ['review', 'arsip'], true), 422);

        $this->resetForm();
        $this->paketAktifId = $paket->id;
        $this->tayangAt = now()->format('Y-m-d\TH:i');
    }

    public function simpanDanArsipkan(): void
    {
        Gate::authorize('upload_publikasi');
        $paket = PaketKonten::findOrFail($this->paketAktifId);
        abort_unless($paket->status === 'review', 422);

        $data = $this->validate([
            'kanalId' => ['required', 'integer', Rule::exists('kanal', 'id')->where('aktif', true)],
            'tayangAt' => ['required', 'date'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'buktiTayang' => ['required', 'image', 'max:5120'],
        ], [], [
            'kanalId' => 'kanal publikasi',
            'tayangAt' => 'waktu tayang',
            'url' => 'URL publikasi',
            'buktiTayang' => 'tangkapan layar tayang',
        ]);

        $pathBukti = $this->buktiTayang->store('publikasi/bukti', 'public');

        try {
            DB::transaction(function () use ($paket, $data, $pathBukti) {
                Publikasi::create([
                    'paket_konten_id' => $paket->id,
                    'kanal_id' => $data['kanalId'],
                    'tayang_at' => $data['tayangAt'],
                    'url' => $data['url'],
                    'evidence_path' => $pathBukti,
                    'pic_id' => Auth::id(),
                ]);

                $paket->update(['status' => 'arsip']);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($pathBukti);
            throw $exception;
        }

        $this->resetForm();
        $this->paketAktifId = null;
        session()->flash('publikasi-tersimpan', 'Publikasi tercatat lengkap dan paket dipindahkan ke arsip.');
    }

    public function editPublikasi(int $publikasiId): void
    {
        Gate::authorize('upload_publikasi');
        $publikasi = Publikasi::findOrFail($publikasiId);

        $this->resetForm();
        $this->publikasiId = $publikasi->id;
        $this->paketAktifId = $publikasi->paket_konten_id;
        $this->kanalId = $publikasi->kanal_id;
        $this->tayangAt = $publikasi->tayang_at->format('Y-m-d\TH:i');
        $this->url = $publikasi->url;
        $this->diubahSetelahTayang = $publikasi->diubah_setelah_tayang;
        $this->alasanPerubahan = $publikasi->alasan_perubahan ?? '';
        $this->dimintaOleh = $publikasi->diminta_oleh ?? '';
    }

    public function simpanPerubahan(): void
    {
        Gate::authorize('upload_publikasi');
        $publikasi = Publikasi::findOrFail($this->publikasiId);

        $data = $this->validate([
            'kanalId' => ['required', 'integer', Rule::exists('kanal', 'id')],
            'tayangAt' => ['required', 'date'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'diubahSetelahTayang' => ['boolean'],
            'alasanPerubahan' => [Rule::requiredIf($this->diubahSetelahTayang), 'nullable', 'string', 'max:5000'],
            'dimintaOleh' => [Rule::requiredIf($this->diubahSetelahTayang), 'nullable', 'string', 'max:255'],
            'buktiTayang' => ['nullable', 'image', 'max:5120'],
        ], [], [
            'kanalId' => 'kanal publikasi',
            'tayangAt' => 'waktu tayang',
            'url' => 'URL publikasi',
            'alasanPerubahan' => 'alasan perubahan',
            'dimintaOleh' => 'pihak yang meminta',
            'buktiTayang' => 'tangkapan layar tayang',
        ]);

        $pathLama = $publikasi->evidence_path;
        $pathBaru = $this->buktiTayang?->store('publikasi/bukti', 'public');

        $publikasi->update([
            'kanal_id' => $data['kanalId'],
            'tayang_at' => $data['tayangAt'],
            'url' => $data['url'],
            'evidence_path' => $pathBaru ?: $pathLama,
            'diubah_setelah_tayang' => $data['diubahSetelahTayang'],
            'alasan_perubahan' => $data['diubahSetelahTayang'] ? $data['alasanPerubahan'] : null,
            'diminta_oleh' => $data['diubahSetelahTayang'] ? $data['dimintaOleh'] : null,
        ]);

        if ($pathBaru && $pathLama) {
            Storage::disk('public')->delete($pathLama);
        }

        $this->resetForm();
        session()->flash('publikasi-tersimpan', 'Catatan publikasi berhasil diperbarui.');
    }

    public function batalEdit(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'publikasiId', 'kanalId', 'tayangAt', 'url', 'buktiTayang',
            'diubahSetelahTayang', 'alasanPerubahan', 'dimintaOleh',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        $paketReview = PaketKonten::query()
            ->where('status', 'review')
            ->latest('updated_at')
            ->get();

        $publikasi = Publikasi::query()
            ->with('kanal')
            ->latest('tayang_at')
            ->get();

        $paketIds = $paketReview->pluck('id')
            ->merge($publikasi->pluck('paket_konten_id'))
            ->filter()
            ->unique();
        $namaPaket = PaketKonten::query()
            ->whereIn('id', $paketIds)
            ->pluck('judul', 'id');

        return view('livewire.kelola-publikasi', [
            'paketReview' => $paketReview,
            'paketAktif' => $this->paketAktifId ? PaketKonten::find($this->paketAktifId) : null,
            'publikasi' => $publikasi,
            'kanal' => Kanal::query()->where('aktif', true)->orderBy('nama')->get(),
            'namaPaket' => $namaPaket,
        ]);
    }
}
