<?php

namespace App\Livewire;

use App\Actions\SimpanCarouselSosmed;
use App\Actions\SimpanVideoSosmed;
use App\Support\PenempatanCarousel;
use App\Support\PenempatanVideoTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Agenda\Models\Agenda;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Content\Models\CatatanPembimbing;
use Modules\People\Models\User;
use Modules\Scheduling\Actions\KonfirmasiPenugasan;
use Modules\Scheduling\Models\Penugasan;
use Modules\Visual\Models\TemplateVisual;
use Modules\Work\Actions\SimpanFotoWebsite;
use Modules\Work\Models\Tugas;
use Modules\Work\Models\TugasBahan;
use Modules\Work\Models\TugasCatatanLapangan;
use RuntimeException;
use ZipArchive;

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

    public string $komentarBaru = '';

    public string $catatanPembimbingBaru = '';

    public string $laporanAtensi = '';

    public string $sambutan = '';

    public string $drafDasarNarasi = '';

    public string $usulanNarasiAi = '';

    public bool $aiTersedia = false;

    public bool $uploaderLampiranTerbuka = false;

    public bool $daftarLampiranTerbuka = false;

    public $unggahan = [];

    public string $langkahWebsite = 'narasi';

    public string $fokusKonten = 'website';

    public string $langkahSosmed = 'caption';

    public string $bahanSosmed = '';

    public string $captionSosmed = '';

    public string $instruksiKoreksiSosmed = '';

    public array $carouselSosmedSlides = [];

    public int $carouselSosmedSlideAktif = 0;

    public bool $carouselSosmedTersimpan = false;

    public ?int $carouselSosmedTemplateId = null;

    public array $videoSosmedScenes = [];

    public int $videoSosmedSceneAktif = 0;

    public string $videoSosmedPreset = 'formal';

    public ?int $videoSosmedTemplateId = null;

    public string $videoSosmedStatus = '';

    public ?string $videoSosmedPath = null;

    public ?string $videoSosmedPesanGagal = null;

    public string $bahanWebsite = '';

    public string $narasiWebsite = '';

    public string $instruksiKoreksiWebsite = '';

    public ?int $fotoWebsiteBahanId = null;

    public array $fotoWebsiteTerpilih = [];

    public array $fotoWebsiteEditor = [];

    public bool $fotoWebsiteTersimpan = false;

    public $fotoWebsiteBaru;

    public float $fotoWebsiteZoom = 1;

    public int $fotoWebsiteFokusX = 50;

    public int $fotoWebsiteFokusY = 50;

    public int $fotoWebsiteRotasi = 0;

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

        $catatanLapangan = TugasCatatanLapangan::query()
            ->where('tugas_id', $this->tugas->id)
            ->where('dibuat_oleh', Auth::id())
            ->first();

        $this->laporanAtensi = $catatanLapangan?->laporan_atensi ?? '';
        $this->sambutan = $catatanLapangan?->sambutan ?? '';
        $this->drafDasarNarasi = $catatanLapangan?->draf_dasar_narasi ?? '';
        $this->usulanNarasiAi = $catatanLapangan?->usulan_ai ?? '';
        $this->bahanWebsite = $catatanLapangan?->bahan_website
            ?? $catatanLapangan?->laporan_atensi
            ?? $catatanLapangan?->sambutan
            ?? $catatanLapangan?->draf_dasar_narasi
            ?? '';
        $this->narasiWebsite = $catatanLapangan?->narasi_website_final
            ?? $catatanLapangan?->usulan_ai
            ?? '';
        $this->instruksiKoreksiWebsite = $catatanLapangan?->instruksi_koreksi_website ?? '';
        $this->bahanSosmed = $catatanLapangan?->bahan_sosmed
            ?? $catatanLapangan?->narasi_website_final
            ?? '';
        $this->captionSosmed = $catatanLapangan?->caption_sosmed_final
            ?? $catatanLapangan?->usulan_ai_sosmed
            ?? '';
        $this->instruksiKoreksiSosmed = $catatanLapangan?->instruksi_koreksi_sosmed ?? '';
        $this->langkahSosmed = $catatanLapangan?->caption_sosmed_final ? 'carousel' : 'caption';
        $this->carouselSosmedSlides = $this->siapkanCarouselSosmed($catatanLapangan);
        $this->carouselSosmedTersimpan = filled($catatanLapangan?->carousel_sosmed_disimpan_at);
        $this->carouselSosmedTemplateId = $catatanLapangan?->carousel_sosmed_template_id
            ?? TemplateVisual::query()->where('format', 'ig_carousel')->where('status', 'aktif')->orderByDesc('versi')->orderByDesc('id')->value('id');
        $templateVideoTersimpan = $catatanLapangan?->video_sosmed_template_id
            ? $this->queryTemplateVideoSosmedAktif()->find($catatanLapangan->video_sosmed_template_id)
            : null;
        $this->videoSosmedTemplateId = $templateVideoTersimpan?->id
            ?? $this->queryTemplateVideoSosmedAktif()->orderByDesc('versi')->orderByDesc('id')->value('id');
        $this->videoSosmedScenes = $this->siapkanVideoSosmed($catatanLapangan);
        $this->videoSosmedStatus = $catatanLapangan?->video_sosmed_status ?? '';
        $this->videoSosmedPath = $catatanLapangan?->video_sosmed_path;
        $this->videoSosmedPesanGagal = $catatanLapangan?->video_sosmed_pesan_gagal;
        $fotoWebsiteItems = $this->fotoWebsiteItemsTersimpan($catatanLapangan);
        foreach ($fotoWebsiteItems as $item) {
            $bahanId = (int) ($item['bahan_id'] ?? 0);
            if ($bahanId < 1) {
                continue;
            }

            $this->fotoWebsiteTerpilih[] = $bahanId;
            $this->fotoWebsiteEditor[$bahanId] = $this->normalisasiEditorFotoWebsite($item);
        }
        $this->fotoWebsiteTerpilih = array_values(array_unique($this->fotoWebsiteTerpilih));
        $this->fotoWebsiteBahanId = $this->fotoWebsiteTerpilih[0] ?? null;
        $this->fotoWebsiteTersimpan = $fotoWebsiteItems !== [];
        $this->muatEditorFotoWebsiteAktif();
        $this->langkahWebsite = $catatanLapangan?->narasi_website_final ? 'foto' : 'narasi';
        $this->aiTersedia = app(PenyediaAi::class)->tersedia();
    }

    public function mulaiKerjakan(): void
    {
        $this->authorizeWork();

        if ($this->tugas->status === 'baru') {
            $this->tugas->update(['status' => 'dikerjakan']);
        }
    }

    public function toggleUploaderLampiran(): void
    {
        $this->uploaderLampiranTerbuka = ! $this->uploaderLampiranTerbuka;
    }

    public function toggleDaftarLampiran(): void
    {
        $this->daftarLampiranTerbuka = ! $this->daftarLampiranTerbuka;
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
        $this->uploaderLampiranTerbuka = false;
        $this->mulaiKerjakan();
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

        $this->tugas->refresh();
        session()->flash(
            'tugas-selesai',
            $this->tugas->status === 'selesai'
                ? 'Tugas selesai dan sudah keluar dari daftar tugas aktif.'
                : 'Bagian Anda selesai. Tugas tetap aktif sampai pelaksana lain menyelesaikan bagiannya.',
        );

        $this->redirectRoute('tugas-saya', navigate: true);
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

    public function simpanCatatanLapangan(): void
    {
        $this->authorizeWork();
        $data = $this->validate([
            'laporanAtensi' => ['nullable', 'string', 'max:20000', 'required_without_all:sambutan,drafDasarNarasi'],
            'sambutan' => ['nullable', 'string', 'max:20000', 'required_without_all:laporanAtensi,drafDasarNarasi'],
            'drafDasarNarasi' => ['nullable', 'string', 'max:30000', 'required_without_all:laporanAtensi,sambutan'],
        ], [
            'laporanAtensi.required_without_all' => 'Isi minimal salah satu catatan lapangan.',
            'sambutan.required_without_all' => 'Isi minimal salah satu catatan lapangan.',
            'drafDasarNarasi.required_without_all' => 'Isi minimal salah satu catatan lapangan.',
        ]);

        TugasCatatanLapangan::updateOrCreate(
            ['tugas_id' => $this->tugas->id, 'dibuat_oleh' => Auth::id()],
            [
                'laporan_atensi' => $data['laporanAtensi'],
                'sambutan' => $data['sambutan'],
                'draf_dasar_narasi' => $data['drafDasarNarasi'],
            ],
        );

        $this->mulaiKerjakan();
        session()->flash('catatan-lapangan-tersimpan', 'Catatan lapangan berhasil disimpan.');
    }

    public function buatNarasiAi(PenyediaAi $penyedia): void
    {
        $this->authorizeWork();
        $data = $this->validate([
            'laporanAtensi' => ['required', 'string', 'max:20000'],
            'sambutan' => ['nullable', 'string', 'max:20000'],
            'drafDasarNarasi' => ['nullable', 'string', 'max:30000'],
        ], [
            'laporanAtensi.required' => 'Laporan atensi wajib diisi sebelum membuat narasi dengan AI.',
        ]);

        if (! $penyedia->tersedia()) {
            $this->addError('aiNarasi', 'Penyedia AI belum dikonfigurasi di server. Bahan narasi tetap tersimpan dan aman.');

            return;
        }

        $sumber = array_values(array_filter([
            'LAPORAN ATENSI:'."\n".trim($data['laporanAtensi']),
            filled($data['sambutan']) ? 'SAMBUTAN ATAU PERNYATAAN:'."\n".trim($data['sambutan']) : null,
            filled($data['drafDasarNarasi']) ? 'DRAF DASAR NARASI:'."\n".trim($data['drafDasarNarasi']) : null,
        ]));

        try {
            $hasil = $penyedia->hasilkan('berita_atensi', $this->tugas->judul, $sumber);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('aiNarasi', 'Narasi AI gagal dibuat. Bahan narasi tetap tersimpan dan aman; silakan coba lagi.');

            return;
        }

        $catatan = TugasCatatanLapangan::updateOrCreate(
            ['tugas_id' => $this->tugas->id, 'dibuat_oleh' => Auth::id()],
            [
                'laporan_atensi' => $data['laporanAtensi'],
                'sambutan' => $data['sambutan'],
                'draf_dasar_narasi' => $data['drafDasarNarasi'],
                'usulan_ai' => trim($hasil->isi),
                'model_ai' => $hasil->model,
                'prompt_versi_ai' => $hasil->promptVersi,
                'dibuat_ai_at' => now(),
            ],
        );

        $this->usulanNarasiAi = $catatan->usulan_ai ?? '';
        $this->mulaiKerjakan();
        session()->flash('narasi-ai-selesai', 'Usulan AI selesai dibuat. Periksa dan sunting sebelum digunakan.');
    }

    public function gunakanUsulanNarasiAi(): void
    {
        $this->authorizeWork();
        abort_if(trim($this->usulanNarasiAi) === '', 422);

        $this->drafDasarNarasi = $this->usulanNarasiAi;
        session()->flash('catatan-lapangan-tersimpan', 'Usulan AI disalin ke draf dasar. Periksa lalu simpan perubahan Anda.');
    }

    public function buatNarasiWebsite(PenyediaAi $penyedia): void
    {
        $this->authorizeWork();
        $data = $this->validate([
            'bahanWebsite' => ['required', 'string', 'max:50000'],
        ], [
            'bahanWebsite.required' => 'Masukkan bahan berita sebelum meminta narasi dari AI.',
        ]);

        if (! $penyedia->tersedia()) {
            $this->addError('aiWebsite', 'AI belum siap digunakan. Bahan berita tetap aman dan dapat disimpan.');

            return;
        }

        try {
            $hasil = $penyedia->hasilkan('berita_atensi', $this->tugas->judul, [$data['bahanWebsite']]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('aiWebsite', 'Narasi website belum berhasil dibuat. Periksa koneksi lalu coba lagi.');

            return;
        }

        $this->narasiWebsite = trim($hasil->isi);
        $this->simpanWorkspaceWebsite([
            'bahan_website' => $data['bahanWebsite'],
            'usulan_ai' => $this->narasiWebsite,
            'model_ai' => $hasil->model,
            'prompt_versi_ai' => $hasil->promptVersi,
            'dibuat_ai_at' => now(),
        ]);
        $this->mulaiKerjakan();
        session()->flash('website-status', 'Narasi website selesai dibuat. Tinjau hasilnya sebelum disimpan.');
    }

    public function koreksiNarasiWebsite(PenyediaAi $penyedia): void
    {
        $this->authorizeWork();
        $data = $this->validate([
            'bahanWebsite' => ['required', 'string', 'max:50000'],
            'narasiWebsite' => ['required', 'string', 'max:50000'],
            'instruksiKoreksiWebsite' => ['required', 'string', 'max:5000'],
        ], [
            'instruksiKoreksiWebsite.required' => 'Tulis bagian yang ingin diperbaiki dari narasi.',
        ]);

        if (! $penyedia->tersedia()) {
            $this->addError('aiWebsite', 'AI belum siap digunakan. Narasi terakhir tetap aman.');

            return;
        }

        try {
            $hasil = $penyedia->hasilkan('koreksi_berita_website', $this->tugas->judul, [
                "BAHAN ASLI:\n".$data['bahanWebsite'],
                "NARASI TERAKHIR:\n".$data['narasiWebsite'],
                "INSTRUKSI KOREKSI:\n".$data['instruksiKoreksiWebsite'],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('aiWebsite', 'Koreksi AI belum berhasil. Narasi terakhir tetap aman.');

            return;
        }

        $this->narasiWebsite = trim($hasil->isi);
        $this->simpanWorkspaceWebsite([
            'bahan_website' => $data['bahanWebsite'],
            'usulan_ai' => $this->narasiWebsite,
            'instruksi_koreksi_website' => $data['instruksiKoreksiWebsite'],
            'model_ai' => $hasil->model,
            'prompt_versi_ai' => $hasil->promptVersi,
            'dibuat_ai_at' => now(),
        ]);
        $this->instruksiKoreksiWebsite = '';
        session()->flash('website-status', 'Koreksi diterapkan. Periksa kembali narasi terbaru.');
    }

    public function simpanNarasiWebsite(): void
    {
        $this->authorizeWork();
        $data = $this->validate([
            'bahanWebsite' => ['required', 'string', 'max:50000'],
            'narasiWebsite' => ['required', 'string', 'max:50000'],
        ]);

        $this->simpanWorkspaceWebsite([
            'bahan_website' => $data['bahanWebsite'],
            'narasi_website_final' => $data['narasiWebsite'],
        ]);
        $this->langkahWebsite = 'foto';
        $this->mulaiKerjakan();
        session()->flash('website-status', 'Narasi website disimpan. Lanjutkan dengan memilih foto utama.');
    }

    public function pilihLangkahWebsite(string $langkah): void
    {
        abort_unless(in_array($langkah, ['narasi', 'foto'], true), 404);

        if ($langkah === 'foto' && ! $this->catatanWebsite()?->narasi_website_final) {
            $this->addError('websiteLangkah', 'Simpan narasi terlebih dahulu sebelum mengedit foto.');

            return;
        }

        $this->langkahWebsite = $langkah;
        $this->resetValidation('websiteLangkah');
    }

    public function pilihFokusKonten(string $fokus): void
    {
        abort_unless(in_array($fokus, ['website', 'sosmed'], true), 404);

        $this->fokusKonten = $fokus;
        $this->resetValidation();
    }

    public function buatCaptionSosmed(PenyediaAi $penyedia): void
    {
        $this->authorizeWork();
        $data = $this->validate([
            'bahanSosmed' => ['required', 'string', 'max:50000'],
        ], [
            'bahanSosmed.required' => 'Masukkan naskah berita sebelum meminta konten dari AI.',
        ]);

        if (! $penyedia->tersedia()) {
            $this->addError('aiSosmed', 'AI belum siap digunakan. Naskah berita tetap aman.');

            return;
        }

        try {
            $hasil = $penyedia->hasilkan('konten_sosmed_pemerintah', $this->tugas->judul, [
                "TEKS LENGKAP NASKAH BERITA:\n".$data['bahanSosmed'],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('aiSosmed', 'Konten media sosial belum berhasil dibuat. Periksa koneksi lalu coba lagi.');

            return;
        }

        $this->captionSosmed = trim($hasil->isi);
        $this->simpanWorkspaceWebsite([
            'bahan_sosmed' => $data['bahanSosmed'],
            'usulan_ai_sosmed' => $this->captionSosmed,
            'model_ai_sosmed' => $hasil->model,
            'prompt_versi_ai_sosmed' => $hasil->promptVersi,
            'dibuat_ai_sosmed_at' => now(),
        ]);
        $this->mulaiKerjakan();
        session()->flash('sosmed-status', 'Konten media sosial selesai dibuat. Tinjau sebelum disimpan.');
    }

    public function koreksiCaptionSosmed(PenyediaAi $penyedia): void
    {
        $this->authorizeWork();
        $data = $this->validate([
            'bahanSosmed' => ['required', 'string', 'max:50000'],
            'captionSosmed' => ['required', 'string', 'max:100000'],
            'instruksiKoreksiSosmed' => ['required', 'string', 'max:5000'],
        ], [
            'instruksiKoreksiSosmed.required' => 'Tulis bagian konten yang ingin diperbaiki.',
        ]);

        if (! $penyedia->tersedia()) {
            $this->addError('aiSosmed', 'AI belum siap digunakan. Konten terakhir tetap aman.');

            return;
        }

        try {
            $hasil = $penyedia->hasilkan('koreksi_konten_sosmed', $this->tugas->judul, [
                "NASKAH ASLI:\n".$data['bahanSosmed'],
                "KONTEN TERAKHIR:\n".$data['captionSosmed'],
                "INSTRUKSI KOREKSI:\n".$data['instruksiKoreksiSosmed'],
            ]);
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('aiSosmed', 'Koreksi AI belum berhasil. Konten terakhir tetap aman.');

            return;
        }

        $this->captionSosmed = trim($hasil->isi);
        $this->simpanWorkspaceWebsite([
            'bahan_sosmed' => $data['bahanSosmed'],
            'usulan_ai_sosmed' => $this->captionSosmed,
            'instruksi_koreksi_sosmed' => $data['instruksiKoreksiSosmed'],
            'model_ai_sosmed' => $hasil->model,
            'prompt_versi_ai_sosmed' => $hasil->promptVersi,
            'dibuat_ai_sosmed_at' => now(),
        ]);
        $this->instruksiKoreksiSosmed = '';
        session()->flash('sosmed-status', 'Koreksi diterapkan. Periksa kembali konten terbaru.');
    }

    public function simpanCaptionSosmed(): void
    {
        $this->authorizeWork();
        $data = $this->validate([
            'bahanSosmed' => ['required', 'string', 'max:50000'],
            'captionSosmed' => ['required', 'string', 'max:100000'],
        ]);

        $this->simpanWorkspaceWebsite([
            'bahan_sosmed' => $data['bahanSosmed'],
            'caption_sosmed_final' => $data['captionSosmed'],
        ]);
        $this->carouselSosmedSlides = $this->siapkanCarouselSosmed($this->catatanWebsite(), paksaDariCaption: true);
        $this->langkahSosmed = 'carousel';
        $this->mulaiKerjakan();
        session()->flash('sosmed-status', 'Caption disimpan. Lanjutkan ke konten carousel IG dan Facebook.');
    }

    public function pilihLangkahSosmed(string $langkah): void
    {
        abort_unless(in_array($langkah, ['caption', 'carousel', 'video'], true), 404);

        if ($langkah !== 'caption' && ! $this->catatanWebsite()?->caption_sosmed_final) {
            $this->addError('sosmedLangkah', 'Simpan caption terlebih dahulu sebelum melanjutkan.');

            return;
        }

        if ($langkah === 'video' && count(array_filter(
            $this->catatanWebsite()?->carousel_sosmed_slides ?? [],
            fn ($slide) => is_array($slide) && isset($slide['path']) && Storage::disk('local')->exists($slide['path']),
        )) !== 3) {
            $this->addError('sosmedLangkah', 'Simpan tiga slide carousel terlebih dahulu sebelum membuat video.');

            return;
        }

        $this->langkahSosmed = $langkah;
        $this->resetValidation('sosmedLangkah');
    }

    public function terapkanPresetVideoSosmed(string $preset): void
    {
        $this->authorizeWork();
        abort_unless(in_array($preset, ['formal', 'halus', 'dinamis'], true), 404);

        $this->videoSosmedPreset = $preset;
        $this->videoSosmedScenes = $this->sceneVideoSosmedUntukPreset($preset);
        $this->videoSosmedSceneAktif = 0;
        $this->videoSosmedStatus = '';
        $this->videoSosmedPath = null;
    }

    public function pilihSceneVideoSosmed(int $index): void
    {
        abort_unless(isset($this->videoSosmedScenes[$index]), 404);
        $this->videoSosmedSceneAktif = $index;
    }

    public function pilihTemplateVideoSosmed(int $templateId): void
    {
        $template = $this->queryTemplateVideoSosmedAktif()->with('layouts')->findOrFail($templateId);
        $this->videoSosmedTemplateId = $template->id;
        $this->videoSosmedScenes = $this->sceneVideoSosmedDariTemplate($template);
        $this->videoSosmedSceneAktif = 0;
        $this->videoSosmedStatus = '';
        $this->videoSosmedPath = null;
    }

    public function simpanVideoSosmed(SimpanVideoSosmed $penyimpan): void
    {
        $this->authorizeWork();
        $template = $this->queryTemplateVideoSosmedAktif()->with(['layouts', 'aset'])->find($this->videoSosmedTemplateId);
        if (! $template) {
            $this->addError('videoSosmedTemplateId', 'Pilih template video vertikal yang aktif.');

            return;
        }

        $carouselSlides = $this->catatanWebsite()?->carousel_sosmed_slides ?? [];
        if (count(array_filter($carouselSlides, fn ($slide) => is_array($slide) && isset($slide['path']) && Storage::disk('local')->exists($slide['path']))) !== 3) {
            $this->addError('videoSosmed', 'Simpan tiga slide carousel terlebih dahulu agar dapat dianimasikan.');

            return;
        }

        $data = $this->validate([
            'videoSosmedScenes' => ['required', 'array', 'size:3'],
            'videoSosmedScenes.*.urutan' => ['required', 'integer', 'between:1,3'],
            'videoSosmedScenes.*.durasi' => ['required', 'integer', 'between:5,12'],
            'videoSosmedScenes.*.gerakan' => ['required', 'string', 'in:zoom_masuk,zoom_keluar,geser_kiri,geser_kanan,diam'],
        ], [
            'videoSosmedScenes.size' => 'Video harus menggunakan tepat tiga slide carousel.',
        ]);

        $this->videoSosmedStatus = 'proses';
        $this->videoSosmedPesanGagal = null;
        try {
            $path = $penyimpan->handle($data['videoSosmedScenes'], $carouselSlides, $this->tugas->id, (int) Auth::id(), $template);
        } catch (\Throwable $exception) {
            report($exception);
            $this->videoSosmedStatus = 'gagal';
            $this->videoSosmedPesanGagal = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Video belum berhasil dibuat. Bahan carousel tetap aman; silakan coba lagi.';
            $this->simpanWorkspaceWebsite([
                'video_sosmed_scenes' => $data['videoSosmedScenes'],
                'video_sosmed_template_id' => $template->id,
                'video_sosmed_template_versi' => $template->versi,
                'video_sosmed_status' => 'gagal',
                'video_sosmed_pesan_gagal' => $this->videoSosmedPesanGagal,
            ]);
            $this->addError('videoSosmed', $this->videoSosmedPesanGagal);

            return;
        }

        $this->videoSosmedStatus = 'selesai';
        $this->videoSosmedPath = $path;
        $this->simpanWorkspaceWebsite([
            'video_sosmed_scenes' => $data['videoSosmedScenes'],
            'video_sosmed_template_id' => $template->id,
            'video_sosmed_template_versi' => $template->versi,
            'video_sosmed_status' => 'selesai',
            'video_sosmed_path' => $path,
            'video_sosmed_pesan_gagal' => null,
            'video_sosmed_disimpan_at' => now(),
        ]);
        session()->flash('sosmed-status', 'Video vertikal 1080 × 1920 px selesai dibuat.');
    }

    public function unduhVideoSosmed()
    {
        $this->authorizeWork();
        $catatan = $this->catatanWebsite();
        abort_unless($catatan?->video_sosmed_status === 'selesai' && $catatan->video_sosmed_path, 404);
        abort_unless(Storage::disk('local')->exists($catatan->video_sosmed_path), 404);

        return Storage::disk('local')->download(
            $catatan->video_sosmed_path,
            $this->namaDasarUnduhanFotoWebsite().'-shorts-tiktok.mp4',
        );
    }

    public function pilihSlideCarouselSosmed(int $index): void
    {
        abort_unless(isset($this->carouselSosmedSlides[$index]), 404);
        $this->carouselSosmedSlideAktif = $index;
    }

    public function pilihTemplateCarouselSosmed(int $templateId): void
    {
        $template = TemplateVisual::query()
            ->where('format', 'ig_carousel')
            ->where('status', 'aktif')
            ->findOrFail($templateId);
        $this->carouselSosmedTemplateId = $template->id;
        $this->carouselSosmedTersimpan = false;
    }

    public function pilihFotoCarouselSosmed(int $bahanId): void
    {
        $this->authorizeWork();
        $this->fotoWebsiteBahan($bahanId);
        if ($this->carouselSosmedSlideAktif === 0) {
            $slot = (int) ($this->carouselSosmedSlides[0]['foto_slot_aktif'] ?? 0);
            $this->carouselSosmedSlides[0]['foto_slots'][$slot]['bahan_id'] = $bahanId;
        } else {
            $this->carouselSosmedSlides[$this->carouselSosmedSlideAktif]['bahan_id'] = $bahanId;
        }
        $this->resetValidation('carouselSosmedSlides.'.$this->carouselSosmedSlideAktif.'.bahan_id');
    }

    public function pilihSlotFotoCarouselSosmed(int $slot): void
    {
        abort_unless($this->carouselSosmedSlideAktif === 0 && isset($this->carouselSosmedSlides[0]['foto_slots'][$slot]), 404);
        $this->carouselSosmedSlides[0]['foto_slot_aktif'] = $slot;
    }

    public function resetEditorCarouselSosmed(): void
    {
        $index = $this->carouselSosmedSlideAktif;
        abort_unless(isset($this->carouselSosmedSlides[$index]), 404);
        if ($index === 0) {
            $slot = (int) ($this->carouselSosmedSlides[0]['foto_slot_aktif'] ?? 0);
            $this->carouselSosmedSlides[0]['foto_slots'][$slot] = [
                ...$this->carouselSosmedSlides[0]['foto_slots'][$slot],
                'zoom' => 1,
                'fokus_x' => 50,
                'fokus_y' => 50,
                'rotasi' => 0,
            ];

            return;
        }
        $this->carouselSosmedSlides[$index] = [
            ...$this->carouselSosmedSlides[$index],
            'zoom' => 1,
            'fokus_x' => 50,
            'fokus_y' => 50,
            'rotasi' => 0,
        ];
    }

    public function resetTeksCarouselSosmed(): void
    {
        $index = $this->carouselSosmedSlideAktif;
        abort_unless(isset($this->carouselSosmedSlides[$index]), 404);
        $this->carouselSosmedSlides[$index] = [
            ...$this->carouselSosmedSlides[$index],
            ...$this->normalisasiUkuranTeksCarouselSosmed([], $index),
        ];
    }

    public function simpanCarouselSosmed(SimpanCarouselSosmed $penyimpan)
    {
        $this->authorizeWork();
        $template = TemplateVisual::with(['aset', 'layouts'])
            ->where('format', 'ig_carousel')
            ->where('status', 'aktif')
            ->find($this->carouselSosmedTemplateId);
        if (! $template || $template->aset->whereIn('jenis', ['background_slide_1', 'background_slide_2', 'background_slide_3'])->count() !== 3) {
            $this->addError('carouselSosmedTemplateId', 'Pilih template carousel yang memiliki tiga background lengkap.');

            return null;
        }
        $data = $this->validate([
            'carouselSosmedSlides' => ['required', 'array', 'size:3'],
            'carouselSosmedSlides.0.kota' => ['required', 'string', 'max:30'],
            'carouselSosmedSlides.0.foto_slots' => ['required', 'array', 'size:3'],
            'carouselSosmedSlides.0.foto_slots.*.bahan_id' => ['required', 'integer'],
            'carouselSosmedSlides.0.foto_slots.*.zoom' => ['required', 'numeric', 'min:1', 'max:3'],
            'carouselSosmedSlides.0.foto_slots.*.fokus_x' => ['required', 'integer', 'between:0,100'],
            'carouselSosmedSlides.0.foto_slots.*.fokus_y' => ['required', 'integer', 'between:0,100'],
            'carouselSosmedSlides.0.foto_slots.*.rotasi' => ['required', 'integer', 'between:-180,180'],
            'carouselSosmedSlides.1.bahan_id' => ['required', 'integer'],
            'carouselSosmedSlides.2.bahan_id' => ['required', 'integer'],
            'carouselSosmedSlides.*.judul' => ['required', 'string', 'max:180'],
            'carouselSosmedSlides.*.isi' => ['required', 'string', 'max:400'],
            'carouselSosmedSlides.*.tanggal' => ['nullable', 'string', 'max:80'],
            'carouselSosmedSlides.0.ukuran_kota' => ['required', 'integer', 'between:10,35'],
            'carouselSosmedSlides.0.ukuran_tanggal' => ['required', 'integer', 'between:10,30'],
            'carouselSosmedSlides.0.ukuran_judul' => ['required', 'integer', 'between:10,50'],
            'carouselSosmedSlides.0.ukuran_isi' => ['required', 'integer', 'between:10,30'],
            'carouselSosmedSlides.1.ukuran_judul' => ['required', 'integer', 'between:20,35'],
            'carouselSosmedSlides.2.ukuran_judul' => ['required', 'integer', 'between:20,35'],
            'carouselSosmedSlides.1.ukuran_isi' => ['required', 'integer', 'between:20,35'],
            'carouselSosmedSlides.2.ukuran_isi' => ['required', 'integer', 'between:20,35'],
            'carouselSosmedSlides.1.zoom' => ['required', 'numeric', 'min:1', 'max:3'],
            'carouselSosmedSlides.1.fokus_x' => ['required', 'integer', 'between:0,100'],
            'carouselSosmedSlides.1.fokus_y' => ['required', 'integer', 'between:0,100'],
            'carouselSosmedSlides.1.rotasi' => ['required', 'integer', 'between:-180,180'],
            'carouselSosmedSlides.2.zoom' => ['required', 'numeric', 'min:1', 'max:3'],
            'carouselSosmedSlides.2.fokus_x' => ['required', 'integer', 'between:0,100'],
            'carouselSosmedSlides.2.fokus_y' => ['required', 'integer', 'between:0,100'],
            'carouselSosmedSlides.2.rotasi' => ['required', 'integer', 'between:-180,180'],
        ], [
            'carouselSosmedSlides.0.foto_slots.*.bahan_id.required' => 'Pilih foto utama dan dua foto pendukung untuk slide 1.',
            'carouselSosmedSlides.*.bahan_id.required' => 'Pilih satu foto untuk slide ini.',
            'carouselSosmedSlides.*.judul.required' => 'Judul slide wajib diisi.',
            'carouselSosmedSlides.*.isi.required' => 'Isi slide wajib diisi.',
            'carouselSosmedSlides.*.isi.max' => 'Isi setiap slide maksimal 400 karakter.',
        ]);

        foreach ([1, 2] as $index) {
            if (count(preg_split('/\R\s*\R/u', trim($data['carouselSosmedSlides'][$index]['isi'])) ?: []) !== 2) {
                $this->addError("carouselSosmedSlides.{$index}.isi", 'Slide 2 dan 3 harus terdiri dari tepat dua paragraf. Pisahkan dengan satu baris kosong.');
            }
        }
        if ($this->getErrorBag()->isNotEmpty()) {
            return null;
        }

        $items = [];
        foreach ($data['carouselSosmedSlides'] as $index => $slide) {
            if ($index === 0) {
                $slide['foto_slots'] = array_map(
                    fn (array $slot) => [...$slot, ...$this->normalisasiEditorFotoWebsite($slot)],
                    $slide['foto_slots'],
                );
                $bahanFoto = array_map(fn (array $slot) => $this->fotoWebsiteBahan((int) $slot['bahan_id']), $slide['foto_slots']);
            } else {
                $slide = [...$slide, ...$this->normalisasiEditorFotoWebsite($slide)];
                $bahanFoto = [$this->fotoWebsiteBahan((int) $slide['bahan_id'])];
            }
            $slide = [...$slide, ...$this->normalisasiUkuranTeksCarouselSosmed($slide, $index), 'urutan' => $index + 1];
            $background = $template->aset->firstWhere('jenis', 'background_slide_'.($index + 1));
            $hasil = $penyimpan->handle($bahanFoto, $this->tugas->id, (int) Auth::id(), $slide, $background?->path, $template);
            $items[] = [...$slide, ...$hasil];
        }

        $this->simpanWorkspaceWebsite([
            'carousel_sosmed_slides' => $items,
            'carousel_sosmed_disimpan_at' => now(),
            'carousel_sosmed_template_id' => $template->id,
            'carousel_sosmed_template_versi' => $template->versi,
        ]);
        $this->carouselSosmedSlides = $items;
        $this->carouselSosmedTersimpan = true;
        session()->flash('sosmed-status', '3 slide carousel 1080 × 1350 px disimpan dan ZIP siap diunduh.');

        return $this->unduhCarouselSosmed();
    }

    public function unduhCarouselSosmed()
    {
        $this->authorizeWork();
        $items = array_values(array_filter(
            $this->catatanWebsite()?->carousel_sosmed_slides ?? [],
            fn ($item) => is_array($item) && isset($item['path']) && Storage::disk('local')->exists($item['path']),
        ));
        abort_unless(count($items) === 3, 404);

        $disk = Storage::disk('local');
        $namaDasar = $this->namaDasarUnduhanFotoWebsite().'-carousel';
        $pathZip = "tugas-sosmed/{$this->tugas->id}/".Auth::id()."/{$namaDasar}.zip";
        $zip = new ZipArchive;
        throw_unless($zip->open($disk->path($pathZip), ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, RuntimeException::class, 'ZIP carousel tidak dapat dibuat.');
        foreach ($items as $index => $item) {
            $zip->addFile($disk->path($item['path']), $namaDasar.'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'.png');
        }
        throw_unless($zip->close(), RuntimeException::class, 'Slide carousel tidak dapat dimasukkan ke ZIP.');

        return $disk->download($pathZip, "{$namaDasar}.zip");
    }

    public function unggahFotoWebsite(): void
    {
        $this->authorizeWork();
        $data = $this->validate([
            'fotoWebsiteBaru' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ], [
            'fotoWebsiteBaru.required' => 'Pilih foto yang akan digunakan.',
            'fotoWebsiteBaru.image' => 'Berkas harus berupa foto JPG, PNG, atau WebP.',
        ]);

        $file = $data['fotoWebsiteBaru'];
        $path = $file->store('tugas-bahan/'.$this->tugas->id);
        $bahan = $this->tugas->bahan()->create([
            'path' => $path,
            'nama_asli' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? 'image/jpeg',
            'diunggah_oleh' => Auth::id(),
        ]);

        $this->fotoWebsiteBaru = null;
        $this->tugas->load('bahan');
        $this->tambahkanFotoWebsiteTerpilih($bahan->id);
        $this->fotoWebsiteBahanId = $bahan->id;
        $this->resetEditorFotoWebsite();
        session()->flash('website-status', 'Foto ditambahkan dan siap diatur.');
    }

    public function pilihFotoWebsite(int $bahanId): void
    {
        $this->authorizeWork();
        $this->fotoWebsiteBahan($bahanId);
        $this->simpanEditorFotoWebsiteAktif();
        if (! in_array($bahanId, $this->fotoWebsiteTerpilih, true) && ! $this->tambahkanFotoWebsiteTerpilih($bahanId)) {
            return;
        }
        $this->fotoWebsiteBahanId = $bahanId;
        $this->muatEditorFotoWebsiteAktif();
    }

    public function toggleFotoWebsite(int $bahanId): void
    {
        $this->authorizeWork();
        $this->fotoWebsiteBahan($bahanId);
        $this->simpanEditorFotoWebsiteAktif();

        if (in_array($bahanId, $this->fotoWebsiteTerpilih, true)) {
            $this->fotoWebsiteTerpilih = array_values(array_filter(
                $this->fotoWebsiteTerpilih,
                fn (int $id) => $id !== $bahanId,
            ));

            if ($this->fotoWebsiteBahanId === $bahanId) {
                $this->fotoWebsiteBahanId = $this->fotoWebsiteTerpilih[0] ?? null;
                $this->muatEditorFotoWebsiteAktif();
            }

            return;
        }

        if (! $this->tambahkanFotoWebsiteTerpilih($bahanId)) {
            return;
        }
        $this->fotoWebsiteBahanId = $bahanId;
        $this->muatEditorFotoWebsiteAktif();
    }

    public function resetEditorFotoWebsite(): void
    {
        $this->fotoWebsiteZoom = 1;
        $this->fotoWebsiteFokusX = 50;
        $this->fotoWebsiteFokusY = 50;
        $this->fotoWebsiteRotasi = 0;
        $this->simpanEditorFotoWebsiteAktif();
    }

    public function updatedFotoWebsiteZoom(): void
    {
        $this->simpanEditorFotoWebsiteAktif();
    }

    public function updatedFotoWebsiteFokusX(): void
    {
        $this->simpanEditorFotoWebsiteAktif();
    }

    public function updatedFotoWebsiteFokusY(): void
    {
        $this->simpanEditorFotoWebsiteAktif();
    }

    public function updatedFotoWebsiteRotasi(): void
    {
        $this->simpanEditorFotoWebsiteAktif();
    }

    public function simpanFotoWebsite(SimpanFotoWebsite $penyimpan)
    {
        $this->authorizeWork();
        $this->simpanEditorFotoWebsiteAktif();
        $data = $this->validate([
            'fotoWebsiteTerpilih' => ['required', 'array', 'min:1', 'max:10'],
            'fotoWebsiteTerpilih.*' => ['required', 'integer', 'distinct'],
        ], [
            'fotoWebsiteTerpilih.required' => 'Pilih minimal satu foto website terlebih dahulu.',
            'fotoWebsiteTerpilih.min' => 'Pilih minimal satu foto website terlebih dahulu.',
            'fotoWebsiteTerpilih.max' => 'Maksimal 10 foto dalam satu ZIP.',
        ]);

        $catatan = $this->catatanWebsite();
        $pathLama = collect($this->fotoWebsiteItemsTersimpan($catatan))
            ->pluck('path')
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->when($catatan?->foto_website_path, fn ($paths, $path) => $paths->push($path))
            ->unique();
        $items = [];

        foreach ($data['fotoWebsiteTerpilih'] as $bahanId) {
            $bahan = $this->fotoWebsiteBahan((int) $bahanId);
            $editor = $this->normalisasiEditorFotoWebsite($this->fotoWebsiteEditor[$bahan->id] ?? []);
            $hasil = $penyimpan->handle(
                $bahan,
                $this->tugas->id,
                (int) Auth::id(),
                $editor['zoom'],
                $editor['fokus_x'],
                $editor['fokus_y'],
                $editor['rotasi'],
            );
            $items[] = [
                'bahan_id' => $bahan->id,
                'path' => $hasil['path'],
                'mime' => $hasil['mime'],
                ...$editor,
            ];
        }

        $fotoPertama = $items[0];
        $this->simpanWorkspaceWebsite([
            'foto_website_bahan_id' => $fotoPertama['bahan_id'],
            'foto_website_path' => $fotoPertama['path'],
            'foto_website_mime' => $fotoPertama['mime'],
            'foto_website_disimpan_at' => now(),
            'foto_website_items' => $items,
        ]);

        $pathBaru = collect($items)->pluck('path');
        $pathLama->diff($pathBaru)->each(fn (string $path) => Storage::disk('local')->delete($path));
        $this->fotoWebsiteTersimpan = true;
        $jumlah = count($items);
        session()->flash('website-status', "{$jumlah} foto website 1050 × 750 px disimpan dan ZIP siap diunduh.");

        return $this->unduhFotoWebsite();
    }

    public function unduhFotoWebsite()
    {
        $this->authorizeWork();
        $catatan = $this->catatanWebsite();
        $items = $this->fotoWebsiteItemsTersimpan($catatan);
        abort_unless($items !== [], 404);

        $disk = Storage::disk('local');
        $items = array_values(array_filter(
            $items,
            fn (array $item) => isset($item['path']) && is_string($item['path']) && $disk->exists($item['path']),
        ));
        abort_unless($items !== [], 404);

        $namaDasar = $this->namaDasarUnduhanFotoWebsite();
        $pathZip = "tugas-website/{$this->tugas->id}/".Auth::id()."/{$namaDasar}.zip";
        $disk->makeDirectory(dirname($pathZip));

        $zip = new ZipArchive;
        $dibuka = $zip->open($disk->path($pathZip), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        throw_unless($dibuka === true, RuntimeException::class, 'ZIP foto website tidak dapat dibuat.');

        $ditambahkan = true;
        foreach ($items as $index => $item) {
            $urutan = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $ditambahkan = $zip->addFile($disk->path($item['path']), "{$namaDasar}-{$urutan}.jpg") && $ditambahkan;
        }
        $ditutup = $zip->close();
        throw_unless($ditambahkan && $ditutup, RuntimeException::class, 'Foto-foto website tidak dapat dimasukkan ke ZIP.');

        return $disk->download($pathZip, "{$namaDasar}.zip");
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
            'templateCarouselSosmed' => TemplateVisual::with(['aset', 'layouts'])
                ->where('format', 'ig_carousel')
                ->where('status', 'aktif')
                ->orderBy('nama')
                ->orderByDesc('versi')
                ->get(),
            'templateVideoSosmed' => $this->queryTemplateVideoSosmedAktif()
                ->with(['aset', 'layouts'])
                ->orderBy('nama')
                ->orderByDesc('versi')
                ->get(),
            'templateVideoSosmedAktif' => $this->queryTemplateVideoSosmedAktif()
                ->with(['aset', 'layouts'])
                ->find($this->videoSosmedTemplateId),
            'penempatanCarouselAktif' => PenempatanCarousel::untukTemplate(
                TemplateVisual::with('layouts')->find($this->carouselSosmedTemplateId),
                $this->carouselSosmedSlideAktif,
            ),
        ]);
    }

    private function authorizeWork(): void
    {
        $this->tugas = Tugas::with(['bahan', 'komentar'])->findOrFail($this->tugas->id);
        Gate::authorize('kerjakan-tugas', $this->tugas);
    }

    private function queryTemplateVideoSosmedAktif()
    {
        return TemplateVisual::query()
            ->where('format', 'video_vertikal')
            ->where('status', 'aktif')
            ->whereHas(
                'layouts',
                fn ($query) => $query->whereIn('jenis', ['video_scene_1', 'video_scene_2', 'video_scene_3']),
                '=',
                3,
            );
    }

    private function catatanWebsite(): ?TugasCatatanLapangan
    {
        return TugasCatatanLapangan::query()
            ->where('tugas_id', $this->tugas->id)
            ->where('dibuat_oleh', Auth::id())
            ->first();
    }

    private function simpanWorkspaceWebsite(array $nilai): TugasCatatanLapangan
    {
        return TugasCatatanLapangan::updateOrCreate(
            ['tugas_id' => $this->tugas->id, 'dibuat_oleh' => Auth::id()],
            $nilai,
        );
    }

    private function fotoWebsiteBahan(int $bahanId): TugasBahan
    {
        $bahan = TugasBahan::query()->where('tugas_id', $this->tugas->id)->findOrFail($bahanId);
        abort_unless(str_starts_with($bahan->mime, 'image/'), 422);

        return $bahan;
    }

    /** @return array<int, array<string, mixed>> */
    private function siapkanCarouselSosmed(?TugasCatatanLapangan $catatan, bool $paksaDariCaption = false): array
    {
        if (! $paksaDariCaption && is_array($catatan?->carousel_sosmed_slides) && count($catatan->carousel_sosmed_slides) === 3) {
            return array_map(
                fn (array $slide, int $index) => [...$slide, ...$this->normalisasiUkuranTeksCarouselSosmed($slide, $index)],
                array_values($catatan->carousel_sosmed_slides),
                array_keys(array_values($catatan->carousel_sosmed_slides)),
            );
        }

        $hasil = $this->ekstrakTeksCarouselSosmed($catatan?->caption_sosmed_final ?? $this->captionSosmed);
        $tanggalAgenda = $this->tugas->agenda_id ? Agenda::find($this->tugas->agenda_id)?->mulai_at?->locale('id')->translatedFormat('d F Y') : '';

        return [
            ['urutan' => 1, 'kota' => $this->kotaCarouselSosmed(), 'tanggal' => $hasil['tanggal'] ?: $tanggalAgenda, 'judul' => $hasil['judul'] ?: $this->tugas->judul, 'isi' => $hasil['subjudul'], 'foto_slot_aktif' => 0, 'foto_slots' => array_fill(0, 3, ['bahan_id' => null, ...$this->normalisasiEditorFotoWebsite([])]), ...$this->normalisasiUkuranTeksCarouselSosmed([], 0)],
            ['urutan' => 2, 'bahan_id' => null, 'tanggal' => '', 'judul' => 'Rangkuman kegiatan', 'isi' => $hasil['halaman_2'], ...$this->normalisasiEditorFotoWebsite([]), ...$this->normalisasiUkuranTeksCarouselSosmed([], 1)],
            ['urutan' => 3, 'bahan_id' => null, 'tanggal' => '', 'judul' => 'Dampak untuk masyarakat', 'isi' => $hasil['halaman_3'], ...$this->normalisasiEditorFotoWebsite([]), ...$this->normalisasiUkuranTeksCarouselSosmed([], 2)],
        ];
    }

    /** @return array{ukuran_judul: int, ukuran_isi: int, ukuran_kota?: int, ukuran_tanggal?: int} */
    private function normalisasiUkuranTeksCarouselSosmed(array $slide, int $index): array
    {
        $hasil = $index === 0
            ? [
                'ukuran_judul' => max(10, min(50, (int) ($slide['ukuran_judul'] ?? 50))),
                'ukuran_isi' => max(10, min(30, (int) ($slide['ukuran_isi'] ?? 30))),
            ]
            : [
                'ukuran_judul' => max(20, min(35, (int) ($slide['ukuran_judul'] ?? 35))),
                'ukuran_isi' => max(20, min(35, (int) ($slide['ukuran_isi'] ?? 35))),
            ];

        if ($index === 0) {
            $hasil['ukuran_kota'] = max(10, min(35, (int) ($slide['ukuran_kota'] ?? 35)));
            $hasil['ukuran_tanggal'] = max(10, min(30, (int) ($slide['ukuran_tanggal'] ?? 30)));
        }

        return $hasil;
    }

    private function kotaCarouselSosmed(): string
    {
        $agenda = $this->tugas->agenda_id ? Agenda::find($this->tugas->agenda_id) : null;
        $lokasi = trim((string) $agenda?->lokasi);
        if ($lokasi === '') {
            return 'BANDUNG';
        }

        return mb_strtoupper(trim(explode(',', $lokasi)[0]));
    }

    /** @return array{tanggal: string, judul: string, subjudul: string, halaman_2: string, halaman_3: string} */
    private function ekstrakTeksCarouselSosmed(string $konten): array
    {
        $bagian = static function (string $pola) use ($konten): string {
            return preg_match($pola, $konten, $cocok) ? trim($cocok[1]) : '';
        };

        return [
            'tanggal' => $bagian('/Tanggal Kegiatan\s*:\s*(.+)/iu'),
            'judul' => $bagian('/Judul Konten\s*:\s*(.+)/iu'),
            'subjudul' => $bagian('/Subjudul\s*:\s*(.+)/iu'),
            'halaman_2' => $bagian('/Halaman\s*2[^\n]*\R(.*?)(?=\R\s*Halaman\s*3|\R\s*1\.B\.|\R\s*CAPTION)/isu'),
            'halaman_3' => $bagian('/Halaman\s*3[^\n]*\R(.*?)(?=\R\s*1\.B\.|\R\s*CAPTION|\R\s*BAGIAN\s*2)/isu'),
        ];
    }

    private function tambahkanFotoWebsiteTerpilih(int $bahanId): bool
    {
        if (in_array($bahanId, $this->fotoWebsiteTerpilih, true)) {
            return true;
        }

        if (count($this->fotoWebsiteTerpilih) >= 10) {
            $this->addError('fotoWebsiteTerpilih', 'Maksimal 10 foto dalam satu ZIP.');

            return false;
        }

        $this->fotoWebsiteTerpilih[] = $bahanId;
        $this->fotoWebsiteEditor[$bahanId] ??= $this->normalisasiEditorFotoWebsite([]);
        $this->resetValidation('fotoWebsiteTerpilih');

        return true;
    }

    private function simpanEditorFotoWebsiteAktif(): void
    {
        if (! $this->fotoWebsiteBahanId) {
            return;
        }

        $this->fotoWebsiteEditor[$this->fotoWebsiteBahanId] = $this->normalisasiEditorFotoWebsite([
            'zoom' => $this->fotoWebsiteZoom,
            'fokus_x' => $this->fotoWebsiteFokusX,
            'fokus_y' => $this->fotoWebsiteFokusY,
            'rotasi' => $this->fotoWebsiteRotasi,
        ]);
    }

    private function muatEditorFotoWebsiteAktif(): void
    {
        if (! $this->fotoWebsiteBahanId) {
            $this->fotoWebsiteZoom = 1;
            $this->fotoWebsiteFokusX = 50;
            $this->fotoWebsiteFokusY = 50;
            $this->fotoWebsiteRotasi = 0;

            return;
        }

        $editor = $this->normalisasiEditorFotoWebsite($this->fotoWebsiteEditor[$this->fotoWebsiteBahanId] ?? []);
        $this->fotoWebsiteEditor[$this->fotoWebsiteBahanId] = $editor;
        $this->fotoWebsiteZoom = $editor['zoom'];
        $this->fotoWebsiteFokusX = $editor['fokus_x'];
        $this->fotoWebsiteFokusY = $editor['fokus_y'];
        $this->fotoWebsiteRotasi = $editor['rotasi'];
    }

    /** @return array{zoom: float, fokus_x: int, fokus_y: int, rotasi: int} */
    private function normalisasiEditorFotoWebsite(array $editor): array
    {
        $rotasi = (int) ($editor['rotasi'] ?? 0);
        while ($rotasi > 180) {
            $rotasi -= 360;
        }
        while ($rotasi < -180) {
            $rotasi += 360;
        }

        return [
            'zoom' => max(1, min(3, (float) ($editor['zoom'] ?? 1))),
            'fokus_x' => max(0, min(100, (int) ($editor['fokus_x'] ?? 50))),
            'fokus_y' => max(0, min(100, (int) ($editor['fokus_y'] ?? 50))),
            'rotasi' => $rotasi,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fotoWebsiteItemsTersimpan(?TugasCatatanLapangan $catatan): array
    {
        if (is_array($catatan?->foto_website_items) && $catatan->foto_website_items !== []) {
            return array_values(array_filter($catatan->foto_website_items, 'is_array'));
        }

        if (! $catatan?->foto_website_path) {
            return [];
        }

        return [[
            'bahan_id' => $catatan->foto_website_bahan_id,
            'path' => $catatan->foto_website_path,
            'mime' => $catatan->foto_website_mime ?? 'image/jpeg',
            ...$this->normalisasiEditorFotoWebsite([]),
        ]];
    }

    private function namaDasarUnduhanFotoWebsite(): string
    {
        $agenda = $this->tugas->agenda_id ? Agenda::find($this->tugas->agenda_id) : null;
        $tanggal = $agenda?->mulai_at ?? $this->tugas->deadline_at ?? now();
        $namaKegiatan = $agenda?->judul ?? $this->tugas->judul;
        $hariKegiatan = $tanggal->copy()->locale('id')->translatedFormat('l-d-F-Y');
        $namaAman = preg_replace('/[^A-Za-z0-9]+/', '-', Str::ascii("{$hariKegiatan}-{$namaKegiatan}"));

        return trim(substr((string) $namaAman, 0, 180), '-');
    }

    /** @return array<int, array<string, mixed>> */
    private function siapkanVideoSosmed(?TugasCatatanLapangan $catatan): array
    {
        if (is_array($catatan?->video_sosmed_scenes) && count($catatan->video_sosmed_scenes) === 3 && isset($catatan->video_sosmed_scenes[0]['urutan'])) {
            return array_values(array_map(fn (array $scene, int $index) => [
                'urutan' => $index + 1,
                'durasi' => max(5, min(12, (int) ($scene['durasi'] ?? [7, 8, 8][$index]))),
                'gerakan' => in_array(($scene['gerakan'] ?? ''), ['zoom_masuk', 'zoom_keluar', 'geser_kiri', 'geser_kanan', 'diam'], true)
                    ? $scene['gerakan']
                    : ['zoom_masuk', 'geser_kiri', 'zoom_keluar'][$index],
            ], $catatan->video_sosmed_scenes, array_keys($catatan->video_sosmed_scenes)));
        }

        $template = TemplateVisual::with('layouts')->find($this->videoSosmedTemplateId);

        return $template ? $this->sceneVideoSosmedDariTemplate($template) : $this->sceneVideoSosmedUntukPreset('formal');
    }

    /** @return array<int, array{urutan: int, durasi: int, gerakan: string}> */
    private function sceneVideoSosmedDariTemplate(TemplateVisual $template): array
    {
        return array_map(function (int $index) use ($template) {
            $scene = PenempatanVideoTemplate::untukTemplate($template, $index);

            return [
                'urutan' => $index + 1,
                'durasi' => max(5, min(12, (int) $scene['durasi'])),
                'gerakan' => 'diam',
            ];
        }, [0, 1, 2]);
    }

    /** @return array<int, array{urutan: int, durasi: int, gerakan: string}> */
    private function sceneVideoSosmedUntukPreset(string $preset): array
    {
        $konfigurasi = match ($preset) {
            'halus' => [[8, 'zoom_masuk'], [8, 'zoom_keluar'], [8, 'zoom_masuk']],
            'dinamis' => [[6, 'zoom_masuk'], [7, 'geser_kiri'], [7, 'geser_kanan']],
            default => [[7, 'zoom_masuk'], [8, 'geser_kiri'], [8, 'zoom_keluar']],
        };

        return array_map(fn (array $scene, int $index) => [
            'urutan' => $index + 1,
            'durasi' => $scene[0],
            'gerakan' => $scene[1],
        ], $konfigurasi, array_keys($konfigurasi));
    }

    public function updatedVideoSosmedScenes(): void
    {
        $this->videoSosmedStatus = '';
        $this->videoSosmedPath = null;
    }

    public function updatedVideoSosmedPreset(): void
    {
        if (in_array($this->videoSosmedPreset, ['formal', 'halus', 'dinamis'], true)) {
            $this->videoSosmedScenes = $this->sceneVideoSosmedUntukPreset($this->videoSosmedPreset);
            $this->videoSosmedSceneAktif = 0;
        }
    }

    /** @return array<int, string> */
    public function labelGerakanVideoSosmed(): array
    {
        return [
            'zoom_masuk' => 'Zoom masuk',
            'zoom_keluar' => 'Zoom keluar',
            'geser_kiri' => 'Geser ke kiri',
            'geser_kanan' => 'Geser ke kanan',
            'diam' => 'Tanpa gerakan',
        ];
    }
}
