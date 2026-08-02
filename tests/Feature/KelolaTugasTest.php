<?php

use App\Actions\SimpanVideoSosmed;
use App\Livewire\KelolaTugas;
use App\Livewire\KerjakanTugas;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Agenda\Models\Agenda;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\HasilAi;
use Modules\Content\Models\CatatanPembimbing;
use Modules\People\Models\Batch;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Scheduling\Models\Penugasan;
use Modules\Scheduling\Models\PeranProduksi;
use Modules\Visual\Models\TemplateAset;
use Modules\Visual\Models\TemplateVisual;
use Modules\Work\Models\Tugas;
use Modules\Work\Models\TugasBahan;
use Modules\Work\Models\TugasCatatanLapangan;

function penggunaTugas(string $email, array $izin = []): User
{
    $role = Role::create(['nama' => $email, 'slug' => str($email)->before('@')->slug()]);
    foreach ($izin as $slug) {
        $role->permissions()->attach(Permission::create(['nama' => $slug, 'slug' => $slug]));
    }

    return User::create(['nama' => $email, 'email' => $email, 'password' => 'password', 'role_id' => $role->id, 'status' => 'aktif']);
}

it('menolak pengguna membuka tugas yang tidak ditugaskan kepadanya', function () {
    $pemilik = penggunaTugas('pemilik@example.com', ['kelola_tugas']);
    $orangLain = penggunaTugas('orang-lain@example.com');
    $tugas = Tugas::create(['judul' => 'Tugas rahasia', 'status' => 'baru', 'dibuat_oleh' => $pemilik->id]);

    Livewire::actingAs($orangLain)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->assertForbidden();
});

it('mengizinkan pelaksana membuka dan menyelesaikan tugasnya', function () {
    $koordinator = penggunaTugas('koordinator@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana@example.com');
    $peran = PeranProduksi::create(['nama' => 'Penulis', 'slug' => 'penulis-tugas', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Susun naskah', 'status' => 'baru', 'dibuat_oleh' => $koordinator->id]);
    $penugasan = Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->call('mulaiKerjakan')
        ->call('tandaiSelesai')
        ->assertRedirect(route('tugas-saya'))
        ->assertSessionHas('tugas-selesai', 'Tugas selesai dan sudah keluar dari daftar tugas aktif.')
        ->assertHasNoErrors();

    expect($tugas->fresh()->status)->toBe('selesai')
        ->and($penugasan->fresh()->status)->toBe('selesai');

    $this->actingAs($pelaksana)
        ->get(route('tugas-saya'))
        ->assertOk()
        ->assertDontSeeText('Susun naskah');
});

it('pelaksana dapat memilih dan mengunggah beberapa bahan kerja', function () {
    Storage::fake('local');
    $koordinator = penggunaTugas('koordinator-bahan@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-bahan@example.com');
    $peran = PeranProduksi::create(['nama' => 'Peliput', 'slug' => 'peliput-bahan', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Liputan berita', 'status' => 'baru', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    $foto = UploadedFile::fake()->image('dokumentasi.jpg');
    $naskah = UploadedFile::fake()->create('naskah-berita.docx', 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->assertSee('Pilih berkas')
        ->set('unggahan', [$foto, $naskah])
        ->assertSee('dokumentasi.jpg')
        ->assertSee('naskah-berita.docx')
        ->call('unggahBahan')
        ->assertHasNoErrors();

    expect($tugas->fresh()->status)->toBe('dikerjakan')
        ->and($tugas->fresh()->bahan)->toHaveCount(2);

    foreach ($tugas->fresh()->bahan as $bahan) {
        Storage::disk('local')->assertExists($bahan->path);
    }
});

it('pelaksana dapat menyimpan dan memperbarui bahan narasi setelah dokumentasi', function () {
    $koordinator = penggunaTugas('koordinator-narasi@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-narasi@example.com');
    $peran = PeranProduksi::create(['nama' => 'Peliput', 'slug' => 'peliput-narasi', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Liputan berita layanan publik', 'status' => 'baru', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('laporanAtensi', 'Kegiatan dihadiri Kepala Divisi dan menghasilkan tiga tindak lanjut.')
        ->set('sambutan', 'Pelayanan hukum harus semakin dekat dengan masyarakat.')
        ->set('drafDasarNarasi', 'Kanwil Kemenkum Jawa Barat memperkuat koordinasi layanan publik.')
        ->call('simpanCatatanLapangan')
        ->assertHasNoErrors()
        ->assertSee('Catatan lapangan berhasil disimpan.')
        ->set('drafDasarNarasi', 'Draf dasar yang sudah diperbarui.')
        ->call('simpanCatatanLapangan')
        ->assertHasNoErrors();

    expect(TugasCatatanLapangan::query()->count())->toBe(1)
        ->and(TugasCatatanLapangan::sole()->dibuat_oleh)->toBe($pelaksana->id)
        ->and(TugasCatatanLapangan::sole()->draf_dasar_narasi)->toBe('Draf dasar yang sudah diperbarui.')
        ->and($tugas->fresh()->status)->toBe('dikerjakan');
});

it('pelaksana dapat membuat usulan berita ai dari laporan atensi tanpa menimpa draf manusia', function () {
    $koordinator = penggunaTugas('koordinator-ai-berita@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-ai-berita@example.com');
    $peran = PeranProduksi::create(['nama' => 'Peliput', 'slug' => 'peliput-ai-berita', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Koordinasi layanan hukum', 'status' => 'baru', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    $penyedia = new class implements PenyediaAi
    {
        public array $permintaan = [];

        public function tersedia(): bool
        {
            return true;
        }

        public function hasilkan(string $jenis, string $judul, array $sumber): HasilAi
        {
            $this->permintaan = compact('jenis', 'judul', 'sumber');

            return new HasilAi(
                isi: "1. Kemenkum Jabar Perkuat Koordinasi\n2. Kemenkum Jabar Hadirkan Layanan\n3. Kemenkum Jabar Dorong Kolaborasi\n4. Kemenkum Jabar Jaga Mutu Layanan\n5. Kemenkum Jabar Bergerak Bersama\n\nBANDUNG - Naskah berita hasil AI.",
                model: 'fake-proud-news',
                promptVersi: 'berita-atensi-v1',
            );
        }
    };
    app()->instance(PenyediaAi::class, $penyedia);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('laporanAtensi', 'Kegiatan berlangsung di Bandung dan dihadiri jajaran Kanwil.')
        ->set('sambutan', 'Arahan pimpinan menekankan layanan berkualitas.')
        ->set('drafDasarNarasi', 'Draf manusia yang tidak boleh ditimpa otomatis.')
        ->call('buatNarasiAi')
        ->assertHasNoErrors()
        ->assertSet('drafDasarNarasi', 'Draf manusia yang tidak boleh ditimpa otomatis.')
        ->assertSet('usulanNarasiAi', fn (string $isi) => str_contains($isi, 'Kemenkum Jabar'));

    $catatan = TugasCatatanLapangan::sole();
    expect($penyedia->permintaan['jenis'])->toBe('berita_atensi')
        ->and($penyedia->permintaan['sumber'])->toContain("LAPORAN ATENSI:\nKegiatan berlangsung di Bandung dan dihadiri jajaran Kanwil.")
        ->and($catatan->draf_dasar_narasi)->toBe('Draf manusia yang tidak boleh ditimpa otomatis.')
        ->and($catatan->usulan_ai)->toContain('Kemenkum Jabar')
        ->and($catatan->model_ai)->toBe('fake-proud-news');
});

it('pelaksana menyusun mengoreksi dan menyimpan narasi website dari satu bahan', function () {
    $koordinator = penggunaTugas('koordinator-website@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-website@example.com');
    $peran = PeranProduksi::create(['nama' => 'Penulis website', 'slug' => 'penulis-website', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Pelayanan hukum di Bandung', 'status' => 'baru', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    $penyedia = new class implements PenyediaAi
    {
        public array $jenis = [];

        public function tersedia(): bool
        {
            return true;
        }

        public function hasilkan(string $jenis, string $judul, array $sumber): HasilAi
        {
            $this->jenis[] = $jenis;

            return new HasilAi(
                isi: $jenis === 'koreksi_berita_website'
                    ? 'BANDUNG - Narasi website yang sudah diperbaiki.'
                    : 'BANDUNG - Narasi website hasil pertama.',
                model: 'fake-website-model',
                promptVersi: 'berita-atensi-v1',
            );
        }
    };
    app()->instance(PenyediaAi::class, $penyedia);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('bahanWebsite', 'Kegiatan pelayanan hukum berlangsung di Bandung.')
        ->call('buatNarasiWebsite')
        ->assertHasNoErrors()
        ->assertSet('narasiWebsite', 'BANDUNG - Narasi website hasil pertama.')
        ->set('instruksiKoreksiWebsite', 'Buat pembuka lebih ringkas.')
        ->call('koreksiNarasiWebsite')
        ->assertHasNoErrors()
        ->assertSet('narasiWebsite', 'BANDUNG - Narasi website yang sudah diperbaiki.')
        ->call('simpanNarasiWebsite')
        ->assertHasNoErrors()
        ->assertSet('langkahWebsite', 'foto');

    $catatan = TugasCatatanLapangan::sole();
    expect($penyedia->jenis)->toBe(['berita_atensi', 'koreksi_berita_website'])
        ->and($catatan->bahan_website)->toBe('Kegiatan pelayanan hukum berlangsung di Bandung.')
        ->and($catatan->narasi_website_final)->toBe('BANDUNG - Narasi website yang sudah diperbaiki.')
        ->and($catatan->instruksi_koreksi_website)->toBe('Buat pembuka lebih ringkas.');
});

it('pelaksana menyusun mengoreksi dan menyimpan paket konten sosmed hanya dari naskah berita', function () {
    $koordinator = penggunaTugas('koordinator-sosmed@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-sosmed@example.com');
    $peran = PeranProduksi::create(['nama' => 'Penulis sosmed', 'slug' => 'penulis-sosmed', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Koordinasi layanan hukum', 'status' => 'baru', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    $penyedia = new class implements PenyediaAi
    {
        public array $jenis = [];

        public array $sumber = [];

        public function tersedia(): bool
        {
            return true;
        }

        public function hasilkan(string $jenis, string $judul, array $sumber): HasilAi
        {
            $this->jenis[] = $jenis;
            $this->sumber[] = $sumber;

            return new HasilAi(
                isi: count($this->jenis) === 1
                    ? "BAGIAN 1\n#WargiPengayoman. Caption awal.\n\n#KementerianHukum\n#LayananHukumMakinMudah\n#KedahWBBM\n#kemenkumjabar\n\nBAGIAN 2\nDampak layanan dirasakan masyarakat.
"
                    : "BAGIAN 1\n#WargiPengayoman. Caption diperbaiki.\n\n#KementerianHukum\n#LayananHukumMakinMudah\n#KedahWBBM\n#kemenkumjabar\n\nBAGIAN 2\nDampak layanan kepada masyarakat diperjelas.",
                model: 'fake-proud-social',
                promptVersi: 'konten-sosmed-v1',
            );
        }
    };
    app()->instance(PenyediaAi::class, $penyedia);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->call('pilihFokusKonten', 'sosmed')
        ->assertSet('fokusKonten', 'sosmed')
        ->set('bahanSosmed', 'Kegiatan berlangsung di Bandung dan memberi kepastian layanan hukum kepada masyarakat.')
        ->call('buatCaptionSosmed')
        ->assertHasNoErrors()
        ->assertSet('captionSosmed', fn (string $isi) => str_contains($isi, 'Caption awal'))
        ->set('instruksiKoreksiSosmed', 'Perjelas dampaknya kepada masyarakat.')
        ->call('koreksiCaptionSosmed')
        ->assertHasNoErrors()
        ->assertSet('captionSosmed', fn (string $isi) => str_contains($isi, 'diperbaiki'))
        ->call('simpanCaptionSosmed')
        ->assertHasNoErrors()
        ->assertSet('langkahSosmed', 'carousel')
        ->call('pilihLangkahSosmed', 'video')
        ->assertSet('langkahSosmed', 'carousel')
        ->assertHasErrors(['sosmedLangkah']);

    $catatan = TugasCatatanLapangan::sole();
    expect($penyedia->jenis)->toBe(['konten_sosmed_pemerintah', 'koreksi_konten_sosmed'])
        ->and($penyedia->sumber[0])->toHaveCount(1)
        ->and($catatan->bahan_sosmed)->toContain('kepastian layanan hukum')
        ->and($catatan->caption_sosmed_final)->toContain('Caption diperbaiki')
        ->and($catatan->model_ai_sosmed)->toBe('fake-proud-social')
        ->and($catatan->prompt_versi_ai_sosmed)->toBe('konten-sosmed-v1');
});

it('pelaksana menghasilkan foto website jpg berukuran tepat 1050 kali 750', function () {
    Storage::fake('local');
    $koordinator = penggunaTugas('koordinator-foto-website@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-foto-website@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor foto website', 'slug' => 'editor-foto-website', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Foto pelayanan hukum', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    $tugas->update(['deadline_at' => '2026-07-29 10:00:00']);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    TugasCatatanLapangan::create([
        'tugas_id' => $tugas->id,
        'dibuat_oleh' => $pelaksana->id,
        'bahan_website' => 'Bahan website.',
        'narasi_website_final' => 'BANDUNG - Narasi final.',
    ]);

    $foto = UploadedFile::fake()->image('dokumentasi.png', 1400, 1000);
    $path = $foto->store('tugas-bahan/'.$tugas->id, 'local');
    $bahan = TugasBahan::create([
        'tugas_id' => $tugas->id,
        'path' => $path,
        'nama_asli' => 'dokumentasi.png',
        'mime' => 'image/png',
        'diunggah_oleh' => $pelaksana->id,
    ]);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->call('pilihFotoWebsite', $bahan->id)
        ->set('fotoWebsiteZoom', 1.25)
        ->set('fotoWebsiteFokusX', 65)
        ->set('fotoWebsiteFokusY', 35)
        ->set('fotoWebsiteRotasi', 17)
        ->call('simpanFotoWebsite')
        ->assertHasNoErrors()
        ->assertFileDownloaded('Rabu-29-Juli-2026-Foto-pelayanan-hukum.zip')
        ->assertSee('1 foto website 1050 × 750 px disimpan dan ZIP siap diunduh.');

    $catatan = TugasCatatanLapangan::sole();
    Storage::disk('local')->assertExists($catatan->foto_website_path);
    [$lebar, $tinggi, $jenis] = getimagesize(Storage::disk('local')->path($catatan->foto_website_path));

    expect($lebar)->toBe(1050)
        ->and($tinggi)->toBe(750)
        ->and($jenis)->toBe(IMAGETYPE_JPEG)
        ->and($catatan->foto_website_bahan_id)->toBe($bahan->id)
        ->and($catatan->foto_website_mime)->toBe('image/jpeg')
        ->and($catatan->foto_website_items)->toHaveCount(1)
        ->and($catatan->foto_website_items[0]['rotasi'])->toBe(17);
});

it('pelaksana memilih beberapa foto dan mengunduh semuanya dalam satu zip', function () {
    Storage::fake('local');
    $koordinator = penggunaTugas('koordinator-multi-foto@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-multi-foto@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor multi foto', 'slug' => 'editor-multi-foto', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Koordinasi Ditjen AHU',
        'mulai_at' => '2026-07-28 09:00:00',
        'selesai_at' => '2026-07-28 11:00:00',
        'status' => 'selesai',
        'dibuat_oleh' => $koordinator->id,
    ]);
    $tugas = Tugas::create(['judul' => 'Edit foto kegiatan', 'status' => 'dikerjakan', 'agenda_id' => $agenda->id, 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    TugasCatatanLapangan::create(['tugas_id' => $tugas->id, 'dibuat_oleh' => $pelaksana->id, 'narasi_website_final' => 'Narasi website.']);

    $bahan = collect([
        ['nama' => 'suasana.png', 'lebar' => 1400, 'tinggi' => 1000],
        ['nama' => 'narasumber.png', 'lebar' => 900, 'tinggi' => 1400],
    ])->map(function (array $foto) use ($tugas, $pelaksana) {
        $unggahan = UploadedFile::fake()->image($foto['nama'], $foto['lebar'], $foto['tinggi']);

        return TugasBahan::create([
            'tugas_id' => $tugas->id,
            'path' => $unggahan->store('tugas-bahan/'.$tugas->id, 'local'),
            'nama_asli' => $foto['nama'],
            'mime' => 'image/png',
            'diunggah_oleh' => $pelaksana->id,
        ]);
    });

    $nama = 'Selasa-28-Juli-2026-Koordinasi-Ditjen-AHU';
    $download = Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->call('pilihFotoWebsite', $bahan[0]->id)
        ->set('fotoWebsiteZoom', 1.2)
        ->call('pilihFotoWebsite', $bahan[1]->id)
        ->set('fotoWebsiteRotasi', -7)
        ->call('simpanFotoWebsite')
        ->assertHasNoErrors()
        ->assertFileDownloaded("{$nama}.zip")
        ->assertSee('2 foto website 1050 × 750 px disimpan dan ZIP siap diunduh.')
        ->effects['download'];

    $catatan = TugasCatatanLapangan::sole();
    expect($catatan->foto_website_items)->toHaveCount(2)
        ->and($catatan->foto_website_items[0]['zoom'])->toBe(1.2)
        ->and($catatan->foto_website_items[1]['rotasi'])->toBe(-7);

    foreach ($catatan->foto_website_items as $item) {
        Storage::disk('local')->assertExists($item['path']);
        expect(getimagesize(Storage::disk('local')->path($item['path']))[0])->toBe(1050)
            ->and(getimagesize(Storage::disk('local')->path($item['path']))[1])->toBe(750);
    }

    $temp = tmpfile();
    fwrite($temp, base64_decode($download['content']));
    $zip = new ZipArchive;
    expect($zip->open(stream_get_meta_data($temp)['uri']))->toBeTrue()
        ->and($zip->numFiles)->toBe(2)
        ->and($zip->getNameIndex(0))->toBe("{$nama}-01.jpg")
        ->and($zip->getNameIndex(1))->toBe("{$nama}-02.jpg");
    $zip->close();
});

it('pelaksana mengedit dan mengunduh tiga slide carousel sosmed berukuran 1080 kali 1350', function () {
    Storage::fake('local');
    $koordinator = penggunaTugas('koordinator-carousel@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-carousel@example.com');
    $peran = PeranProduksi::create(['nama' => 'Desainer carousel', 'slug' => 'desainer-carousel', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Koordinasi Ditjen AHU',
        'mulai_at' => '2026-07-28 09:00:00',
        'selesai_at' => '2026-07-28 11:00:00',
        'status' => 'selesai',
        'dibuat_oleh' => $koordinator->id,
    ]);
    $tugas = Tugas::create(['judul' => 'Konten koordinasi AHU', 'status' => 'dikerjakan', 'agenda_id' => $agenda->id, 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    $caption = <<<'TEXT'
1.A. KONTEN INFOGRAFIS (3 HALAMAN)
Halaman 1: Judul Utama
Tanggal Kegiatan: 28 Juli 2026
Judul Konten: Koordinasi Perkuat Layanan AHU
Subjudul: Sinergi untuk layanan hukum yang makin mudah.

Halaman 2: Rangkuman Awal
Kemenkum Jabar berkoordinasi dengan Ditjen AHU di Bandung.

Pertemuan membahas peningkatan kualitas layanan administrasi hukum.

Halaman 3: Rangkuman Lanjutan
Langkah tindak lanjut disusun bersama agar pelayanan lebih pasti.

Masyarakat diharapkan memperoleh akses layanan yang lebih cepat.

1.B. CAPTION UNIVERSAL & DESKRIPSI
TEXT;
    TugasCatatanLapangan::create(['tugas_id' => $tugas->id, 'dibuat_oleh' => $pelaksana->id, 'caption_sosmed_final' => $caption]);

    $bahan = collect(['cover.png', 'diskusi.png', 'layanan.png'])->map(function (string $nama) use ($tugas, $pelaksana) {
        $foto = UploadedFile::fake()->image($nama, 1400, 1000);

        return TugasBahan::create([
            'tugas_id' => $tugas->id,
            'path' => $foto->store('tugas-bahan/'.$tugas->id, 'local'),
            'nama_asli' => $nama,
            'mime' => 'image/png',
            'diunggah_oleh' => $pelaksana->id,
        ]);
    });

    $template = TemplateVisual::create(['nama' => 'Kegiatan Kanwil', 'format' => 'ig_carousel', 'rasio' => '4:5', 'versi' => 1, 'status' => 'aktif', 'dibuat_oleh' => $koordinator->id]);
    foreach ([1, 2, 3] as $urutan) {
        $pathBackground = "template-visual/{$template->id}/slide-{$urutan}.png";
        Storage::disk('local')->put($pathBackground, UploadedFile::fake()->image("background-{$urutan}.png", 1080, 1350)->getContent());
        TemplateAset::create(['template_visual_id' => $template->id, 'jenis' => "background_slide_{$urutan}", 'path' => $pathBackground]);
    }

    $nama = 'Selasa-28-Juli-2026-Koordinasi-Ditjen-AHU-carousel';
    $halaman = Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->assertSet('langkahSosmed', 'carousel')
        ->assertSet('carouselSosmedTemplateId', $template->id)
        ->assertSet('carouselSosmedSlides.0.judul', 'Koordinasi Perkuat Layanan AHU')
        ->call('pilihFotoCarouselSosmed', $bahan[0]->id)
        ->call('pilihSlotFotoCarouselSosmed', 1)
        ->call('pilihFotoCarouselSosmed', $bahan[1]->id)
        ->call('pilihSlotFotoCarouselSosmed', 2)
        ->call('pilihFotoCarouselSosmed', $bahan[2]->id)
        ->call('pilihSlideCarouselSosmed', 1)
        ->call('pilihFotoCarouselSosmed', $bahan[1]->id)
        ->set('carouselSosmedSlides.1.rotasi', 7)
        ->set('carouselSosmedSlides.1.ukuran_judul', 30)
        ->set('carouselSosmedSlides.1.ukuran_isi', 20)
        ->call('pilihSlideCarouselSosmed', 2)
        ->call('pilihFotoCarouselSosmed', $bahan[2]->id)
        ->call('simpanCarouselSosmed')
        ->assertHasNoErrors()
        ->assertFileDownloaded("{$nama}.zip");

    $catatan = TugasCatatanLapangan::sole();
    expect($catatan->carousel_sosmed_slides)->toHaveCount(3)
        ->and($catatan->carousel_sosmed_slides[1]['rotasi'])->toBe(7)
        ->and($catatan->carousel_sosmed_slides[1]['ukuran_judul'])->toBe(30)
        ->and($catatan->carousel_sosmed_slides[1]['ukuran_isi'])->toBe(20)
        ->and($catatan->carousel_sosmed_slides[0]['foto_slots'])->toHaveCount(3)
        ->and($catatan->carousel_sosmed_disimpan_at)->not->toBeNull();
    expect($catatan->carousel_sosmed_template_id)->toBe($template->id)
        ->and($catatan->carousel_sosmed_template_versi)->toBe(1);

    $htmlSlideDua = Storage::disk('local')->get("tugas-sosmed/{$tugas->id}/{$pelaksana->id}/carousel-02.html");
    expect($htmlSlideDua)->toContain('font-family: Roboto')
        ->toContain('font-size: 27px')
        ->not->toContain('font-size: 20pt');

    foreach ($catatan->carousel_sosmed_slides as $slide) {
        Storage::disk('local')->assertExists($slide['path']);
        [$lebar, $tinggi, $jenis] = getimagesize(Storage::disk('local')->path($slide['path']));
        expect($lebar)->toBe(1080)->and($tinggi)->toBe(1350)->and($jenis)->toBe(IMAGETYPE_PNG);
    }

    $download = $halaman->effects['download'];
    $temp = tmpfile();
    fwrite($temp, base64_decode($download['content']));
    $zip = new ZipArchive;
    expect($zip->open(stream_get_meta_data($temp)['uri']))->toBeTrue()
        ->and($zip->numFiles)->toBe(3)
        ->and($zip->getNameIndex(0))->toBe("{$nama}-01.png")
        ->and($zip->getNameIndex(2))->toBe("{$nama}-03.png");
    $zip->close();
});

it('halaman kerja memakai kanvas lebar dan layout editor yang padat', function () {
    $koordinator = penggunaTugas('koordinator-layout-padat@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-layout-padat@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor layout padat', 'slug' => 'editor-layout-padat', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Layout ruang kerja', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->assertSeeHtml('<main class="mx-auto max-w-7xl space-y-4 px-4 py-4 sm:px-6 sm:py-5 lg:px-8">')
        ->assertDontSee('Komentar')
        ->assertDontSee('Catatan pembimbing');
});

it('editor video sosmed menyusun scene dan menyimpan hasil mp4', function () {
    Storage::fake('local');
    $koordinator = penggunaTugas('koordinator-video-sosmed@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-video-sosmed@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor video sosmed', 'slug' => 'editor-video-sosmed', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Video layanan hukum', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    TugasCatatanLapangan::create(['tugas_id' => $tugas->id, 'dibuat_oleh' => $pelaksana->id, 'caption_sosmed_final' => 'Caption tersimpan.']);
    $catatan = TugasCatatanLapangan::sole();
    $slides = collect([1, 2, 3])->map(function (int $urutan) use ($tugas, $pelaksana) {
        $path = "tugas-sosmed/{$tugas->id}/{$pelaksana->id}/carousel-0{$urutan}.png";
        Storage::disk('local')->put($path, 'slide');

        return ['urutan' => $urutan, 'path' => $path, 'mime' => 'image/png'];
    })->all();
    $catatan->update(['carousel_sosmed_slides' => $slides, 'carousel_sosmed_disimpan_at' => now()]);
    $this->actingAs($pelaksana)
        ->get(route('tugas.carousel.hasil', [$tugas, 1]))
        ->assertOk()
        ->assertHeader('content-type', 'image/png');
    $template = TemplateVisual::create(['nama' => 'Shorts Kanwil', 'format' => 'video_vertikal', 'rasio' => '9:16', 'versi' => 1, 'status' => 'aktif', 'durasi_per_slide_detik' => 4, 'dibuat_oleh' => $koordinator->id]);
    foreach ([1, 2, 3] as $urutan) {
        $template->layouts()->create([
            'jenis' => "video_scene_{$urutan}",
            'definisi' => ['durasi' => $urutan === 1 ? 6 : 8, 'layers' => [[
                'id' => 'foto', 'nama' => 'Area foto', 'jenis' => 'foto', 'x' => 70, 'y' => 245,
                'lebar' => 940, 'tinggi' => 650, 'urutan' => 20, 'animasi' => 'fade_in',
                'mulai' => .7, 'durasi_animasi' => .7,
            ]]],
            'batas_karakter' => [],
        ]);
    }

    $penyimpan = Mockery::mock(SimpanVideoSosmed::class);
    $penyimpan->shouldReceive('handle')
        ->once()
        ->withArgs(fn (array $scenes, array $carousel, int $tugasId, int $userId, TemplateVisual $templateDiterima) => count($scenes) === 3
            && count($carousel) === 3
            && $scenes[0]['gerakan'] === 'zoom_masuk'
            && $tugasId === $tugas->id
            && $userId === $pelaksana->id
            && $templateDiterima->is($template))
        ->andReturn("tugas-sosmed/{$tugas->id}/{$pelaksana->id}/video/video-sosmed.mp4");
    app()->instance(SimpanVideoSosmed::class, $penyimpan);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('fokusKonten', 'sosmed')
        ->call('pilihLangkahSosmed', 'video')
        ->assertSee('Ubah carousel menjadi video')
        ->assertSee('Tiga slide carousel')
        ->assertSee('Preview video')
        ->assertSet('videoSosmedScenes.0.durasi', 6)
        ->call('terapkanPresetVideoSosmed', 'formal')
        ->set('videoSosmedScenes.0.durasi', 6)
        ->call('simpanVideoSosmed')
        ->assertHasNoErrors()
        ->assertSet('videoSosmedStatus', 'selesai')
        ->assertSet('videoSosmedTemplateId', $template->id);

    $catatan = TugasCatatanLapangan::sole();
    expect($catatan->video_sosmed_scenes)->toHaveCount(3)
        ->and($catatan->video_sosmed_scenes[0]['urutan'])->toBe(1)
        ->and($catatan->video_sosmed_scenes[0]['durasi'])->toBe(6)
        ->and($catatan->video_sosmed_scenes[0]['gerakan'])->toBe('zoom_masuk')
        ->and($catatan->video_sosmed_status)->toBe('selesai');
});

it('preview shorts dan tiktok memakai layout serta aset template video terpilih', function () {
    Storage::fake('local');
    $koordinator = penggunaTugas('koordinator-preview-video-template@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-preview-video-template@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor preview video', 'slug' => 'editor-preview-video', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Preview template shorts', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    $slides = collect([1, 2, 3])->map(function (int $urutan) use ($tugas, $pelaksana) {
        $path = "tugas-sosmed/{$tugas->id}/{$pelaksana->id}/carousel-0{$urutan}.png";
        Storage::disk('local')->put($path, UploadedFile::fake()->image("slide-{$urutan}.png", 1080, 1350)->getContent());

        return ['urutan' => $urutan, 'path' => $path, 'mime' => 'image/png', 'judul' => "Judul scene {$urutan}", 'isi' => "Paragraf pertama.\n\nParagraf kedua."];
    })->all();
    TugasCatatanLapangan::create([
        'tugas_id' => $tugas->id, 'dibuat_oleh' => $pelaksana->id, 'caption_sosmed_final' => 'Caption tersimpan.',
        'carousel_sosmed_slides' => $slides, 'carousel_sosmed_disimpan_at' => now(),
    ]);
    $template = TemplateVisual::create(['nama' => 'Template Layer Kanwil', 'format' => 'video_vertikal', 'rasio' => '9:16', 'versi' => 2, 'status' => 'aktif', 'dibuat_oleh' => $koordinator->id]);
    foreach ([1, 2, 3] as $urutan) {
        $template->layouts()->create([
            'jenis' => "video_scene_{$urutan}",
            'definisi' => ['durasi' => 8, 'layers' => [
                ['id' => 'header', 'nama' => 'Header identitas', 'jenis' => 'png', 'x' => 0, 'y' => 0, 'lebar' => 1080, 'tinggi' => 180, 'urutan' => 10, 'animasi' => 'fade_in', 'mulai' => .2, 'durasi_animasi' => .6],
                ['id' => 'foto', 'nama' => 'Foto', 'jenis' => 'foto', 'x' => 70, 'y' => 240, 'lebar' => 940, 'tinggi' => 690, 'urutan' => 20, 'animasi' => 'naik', 'mulai' => .6, 'durasi_animasi' => .7],
                ['id' => 'judul', 'nama' => 'Judul', 'jenis' => 'judul', 'x' => 70, 'y' => 1030, 'lebar' => 940, 'tinggi' => 240, 'urutan' => 30, 'animasi' => 'masuk_kiri', 'mulai' => 1.1, 'durasi_animasi' => .6],
            ]],
            'batas_karakter' => [],
        ]);
    }
    $headerPath = "template-visual/{$template->id}/video/header.png";
    Storage::disk('local')->put($headerPath, UploadedFile::fake()->image('header.png', 1080, 180)->getContent());
    $template->aset()->create(['jenis' => 'video_scene_1_header', 'path' => $headerPath]);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('fokusKonten', 'sosmed')
        ->call('pilihLangkahSosmed', 'video')
        ->assertSet('videoSosmedTemplateId', $template->id)
        ->assertSee('Template Layer Kanwil')
        ->assertSee('3/3 scene · 1 PNG')
        ->assertSee('Header identitas')
        ->assertSeeHtml('data-proud-animation="fade_in"')
        ->assertSeeHtml('data-proud-animation="masuk_kiri"')
        ->assertSee('Judul scene 1');
});

it('editor video memilih versi aktif paling baru sebagai template awal', function () {
    $koordinator = penggunaTugas('koordinator-versi-video@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-versi-video@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor versi video', 'slug' => 'editor-versi-video', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Versi template video', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    TugasCatatanLapangan::create(['tugas_id' => $tugas->id, 'dibuat_oleh' => $pelaksana->id, 'caption_sosmed_final' => 'Caption tersimpan.']);

    foreach ([1, 3] as $versi) {
        $template = TemplateVisual::create([
            'nama' => 'Video Vertikal Kanwil', 'format' => 'video_vertikal', 'rasio' => '9:16',
            'versi' => $versi, 'status' => 'aktif', 'dibuat_oleh' => $koordinator->id,
        ]);
        foreach ([1, 2, 3] as $urutan) {
            $template->layouts()->create([
                'jenis' => "video_scene_{$urutan}",
                'definisi' => ['durasi' => 8, 'layers' => [[
                    'id' => 'foto', 'nama' => 'Foto', 'jenis' => 'foto', 'x' => 70, 'y' => 245,
                    'lebar' => 940, 'tinggi' => 650, 'urutan' => 20, 'animasi' => 'fade_in',
                    'mulai' => .7, 'durasi_animasi' => .7,
                ]]],
                'batas_karakter' => [],
            ]);
        }
    }

    $terbaru = TemplateVisual::where('versi', 3)->sole();

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('fokusKonten', 'sosmed')
        ->call('pilihLangkahSosmed', 'video')
        ->assertSet('videoSosmedTemplateId', $terbaru->id);
});

it('editor meninggalkan pilihan lama yang sudah diarsipkan dan memakai versi aktif terbaru', function () {
    $koordinator = penggunaTugas('koordinator-upgrade-video@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-upgrade-video@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor upgrade video', 'slug' => 'editor-upgrade-video', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Upgrade template video', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    $templates = collect();
    foreach ([2 => 'arsip', 4 => 'aktif'] as $versi => $status) {
        $template = TemplateVisual::create([
            'nama' => 'Video Vertikal Kanwil', 'format' => 'video_vertikal', 'rasio' => '9:16',
            'versi' => $versi, 'status' => $status, 'dibuat_oleh' => $koordinator->id,
        ]);
        foreach ([1, 2, 3] as $urutan) {
            $template->layouts()->create([
                'jenis' => "video_scene_{$urutan}",
                'definisi' => ['durasi' => 8, 'layers' => [[
                    'id' => 'foto', 'nama' => 'Foto', 'jenis' => 'foto', 'x' => 70, 'y' => 245,
                    'lebar' => 940, 'tinggi' => 650, 'urutan' => 20, 'animasi' => 'fade_in',
                    'mulai' => .7, 'durasi_animasi' => .7,
                ]]],
                'batas_karakter' => [],
            ]);
        }
        $templates->put($versi, $template);
    }

    TugasCatatanLapangan::create([
        'tugas_id' => $tugas->id,
        'dibuat_oleh' => $pelaksana->id,
        'caption_sosmed_final' => 'Caption tersimpan.',
        'video_sosmed_template_id' => $templates->get(2)->id,
        'video_sosmed_template_versi' => 2,
    ]);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('fokusKonten', 'sosmed')
        ->call('pilihLangkahSosmed', 'video')
        ->assertSet('videoSosmedTemplateId', $templates->get(4)->id);
});

it('carousel menyediakan ukuran teks per slide dan kontrol individual untuk setiap slot foto cover', function () {
    Storage::fake('local');
    $koordinator = penggunaTugas('koordinator-kontrol-carousel@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-kontrol-carousel@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor kontrol carousel', 'slug' => 'editor-kontrol-carousel', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Kontrol carousel', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    TugasCatatanLapangan::create(['tugas_id' => $tugas->id, 'dibuat_oleh' => $pelaksana->id, 'caption_sosmed_final' => 'Caption tersimpan.']);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('fokusKonten', 'sosmed')
        ->assertSet('carouselSosmedSlides.0.ukuran_kota', 35)
        ->assertSet('carouselSosmedSlides.0.ukuran_tanggal', 30)
        ->assertSet('carouselSosmedSlides.0.ukuran_judul', 50)
        ->assertSet('carouselSosmedSlides.1.ukuran_judul', 35)
        ->set('carouselSosmedSlides.0.foto_slots.0.zoom', 1.25)
        ->call('pilihSlotFotoCarouselSosmed', 1)
        ->set('carouselSosmedSlides.0.foto_slots.1.rotasi', -12)
        ->assertSet('carouselSosmedSlides.0.foto_slots.0.zoom', 1.25)
        ->assertSet('carouselSosmedSlides.0.foto_slots.1.rotasi', -12)
        ->set('carouselSosmedSlides.0.ukuran_judul', 10)
        ->call('resetTeksCarouselSosmed')
        ->assertSet('carouselSosmedSlides.0.ukuran_judul', 50)
        ->assertSee('Edit slide 1')
        ->assertSee('Daftar foto')
        ->assertSee('Workarea slide 1')
        ->assertSee('Atur foto kanan atas');
});

it('preview slide dua dan tiga mengikuti area foto dan paragraf pada background template', function () {
    Storage::fake('local');
    $koordinator = penggunaTugas('koordinator-layout-carousel@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-layout-carousel@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor layout carousel', 'slug' => 'editor-layout-carousel', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Layout carousel', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    TugasCatatanLapangan::create(['tugas_id' => $tugas->id, 'dibuat_oleh' => $pelaksana->id, 'caption_sosmed_final' => 'Caption tersimpan.']);

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('fokusKonten', 'sosmed')
        ->call('pilihSlideCarouselSosmed', 1)
        ->assertSeeHtml('left:12.87037037037%;top:13.851851851852%;width:79.351851851852%;height:43.333333333333%')
        ->assertSeeHtml('left:8.7962962962963%;top:62.962962962963%;width:82.407407407407%;height:27.555555555556%')
        ->assertDontSee('Judul slide');
});

it('carousel sosmed mewajibkan tepat dua paragraf pada slide dua dan tiga', function () {
    Storage::fake('local');
    $koordinator = penggunaTugas('koordinator-validasi-carousel@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-validasi-carousel@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor carousel', 'slug' => 'editor-validasi-carousel', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Carousel layanan hukum', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    TugasCatatanLapangan::create(['tugas_id' => $tugas->id, 'dibuat_oleh' => $pelaksana->id, 'caption_sosmed_final' => 'Caption tersimpan.']);
    $foto = UploadedFile::fake()->image('foto.png', 1400, 1000);
    $bahan = TugasBahan::create(['tugas_id' => $tugas->id, 'path' => $foto->store('tugas-bahan/'.$tugas->id, 'local'), 'nama_asli' => 'foto.png', 'mime' => 'image/png', 'diunggah_oleh' => $pelaksana->id]);
    $template = TemplateVisual::create(['nama' => 'Validasi Carousel', 'format' => 'ig_carousel', 'rasio' => '4:5', 'versi' => 1, 'status' => 'aktif', 'dibuat_oleh' => $koordinator->id]);
    foreach ([1, 2, 3] as $urutan) {
        $pathBackground = "template-visual/{$template->id}/slide-{$urutan}.png";
        Storage::disk('local')->put($pathBackground, UploadedFile::fake()->image("background-{$urutan}.png", 1080, 1350)->getContent());
        TemplateAset::create(['template_visual_id' => $template->id, 'jenis' => "background_slide_{$urutan}", 'path' => $pathBackground]);
    }

    Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('carouselSosmedSlides.0.foto_slots.0.bahan_id', $bahan->id)
        ->set('carouselSosmedSlides.0.foto_slots.1.bahan_id', $bahan->id)
        ->set('carouselSosmedSlides.0.foto_slots.2.bahan_id', $bahan->id)
        ->set('carouselSosmedSlides.0.isi', 'Subjudul cover.')
        ->set('carouselSosmedSlides.1.bahan_id', $bahan->id)
        ->set('carouselSosmedSlides.1.isi', 'Hanya satu paragraf.')
        ->set('carouselSosmedSlides.2.bahan_id', $bahan->id)
        ->set('carouselSosmedSlides.2.isi', "Paragraf pertama.\n\nParagraf kedua.")
        ->call('simpanCarouselSosmed')
        ->assertHasErrors(['carouselSosmedSlides.1.isi']);
});

it('pelaksana mengunduh foto website dalam zip bernama hari dan kegiatan', function () {
    Storage::fake('local');
    $koordinator = penggunaTugas('koordinator-unduh-foto@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('pelaksana-unduh-foto@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor unduhan website', 'slug' => 'editor-unduhan-website', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Koordinasi Ditjen AHU',
        'mulai_at' => '2026-07-28 09:00:00',
        'selesai_at' => '2026-07-28 11:00:00',
        'status' => 'selesai',
        'dibuat_oleh' => $koordinator->id,
    ]);
    $tugas = Tugas::create([
        'judul' => 'Edit foto kegiatan',
        'status' => 'dikerjakan',
        'agenda_id' => $agenda->id,
        'dibuat_oleh' => $koordinator->id,
    ]);
    Penugasan::create(['user_id' => $pelaksana->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    Storage::disk('local')->put("tugas-website/{$tugas->id}/{$pelaksana->id}/foto-website.jpg", 'foto-jpg');
    TugasCatatanLapangan::create([
        'tugas_id' => $tugas->id,
        'dibuat_oleh' => $pelaksana->id,
        'bahan_website' => 'Bahan website.',
        'narasi_website_final' => 'Narasi website.',
        'foto_website_path' => "tugas-website/{$tugas->id}/{$pelaksana->id}/foto-website.jpg",
        'foto_website_mime' => 'image/jpeg',
    ]);

    $nama = 'Selasa-28-Juli-2026-Koordinasi-Ditjen-AHU';
    $download = Livewire::actingAs($pelaksana)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->call('unduhFotoWebsite')
        ->assertFileDownloaded("{$nama}.zip")
        ->effects['download'];

    $temp = tmpfile();
    fwrite($temp, base64_decode($download['content']));
    $zip = new ZipArchive;

    expect($zip->open(stream_get_meta_data($temp)['uri']))->toBeTrue()
        ->and($zip->numFiles)->toBe(1)
        ->and($zip->getNameIndex(0))->toBe("{$nama}-01.jpg")
        ->and($zip->getFromIndex(0))->toBe('foto-jpg');

    $zip->close();
});

it('menyelesaikan tugas setelah tidak ada penugasan aktif yang tersisa', function () {
    $koordinator = penggunaTugas('koordinator-selesai-bersama@example.com', ['kelola_tugas']);
    $pelaksanaSatu = penggunaTugas('pelaksana-satu-selesai@example.com');
    $pelaksanaDua = penggunaTugas('pelaksana-dua-selesai@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor', 'slug' => 'editor-selesai-bersama', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Kerja bersama', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    $penugasanSatu = Penugasan::create(['user_id' => $pelaksanaSatu->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    $penugasanDua = Penugasan::create(['user_id' => $pelaksanaDua->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    Livewire::actingAs($pelaksanaSatu)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->call('tandaiSelesai')
        ->assertRedirect(route('tugas-saya'))
        ->assertSessionHas('tugas-selesai', 'Bagian Anda selesai. Tugas tetap aktif sampai pelaksana lain menyelesaikan bagiannya.')
        ->assertHasNoErrors();

    expect($penugasanSatu->fresh()->status)->toBe('selesai')
        ->and($penugasanDua->fresh()->status)->toBe('aktif')
        ->and($tugas->fresh()->status)->toBe('dikerjakan');

    Livewire::actingAs($pelaksanaDua)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->call('tandaiSelesai')
        ->assertHasNoErrors();

    expect($tugas->fresh()->status)->toBe('selesai');
});

it('membuat tugas dan menambahkan pelaksana berdeadline', function () {
    $koordinator = penggunaTugas('kelola@example.com', ['kelola_tugas']);
    $pelaksana = penggunaTugas('anggota@example.com');
    $peran = PeranProduksi::create(['nama' => 'Editor', 'slug' => 'editor-tugas', 'aktif' => true]);

    $halaman = Livewire::actingAs($koordinator)
        ->test(KelolaTugas::class)
        ->call('buat')
        ->set('judul', 'Edit berita pelayanan publik')
        ->set('brief', 'Rapikan judul dan lead.')
        ->set('deadlineAt', '2026-08-03T17:00')
        ->call('simpan')
        ->assertHasNoErrors();

    $tugas = Tugas::sole();
    $halaman
        ->call('aturPelaksana', $tugas->id)
        ->set('anggotaId', (string) $pelaksana->id)
        ->set('peranId', (string) $peran->id)
        ->set('deadlinePenugasanAt', '2026-08-03T17:00')
        ->call('simpanPenugasan')
        ->assertHasNoErrors();

    $penugasan = Penugasan::sole();
    expect($penugasan->user_id)->toBe($pelaksana->id)
        ->and($penugasan->untuk_type)->toBe('tugas')
        ->and($penugasan->untuk_id)->toBe($tugas->id);
});

it('mewajibkan pembimbing ketika pelaksana memiliki batch magang', function () {
    $koordinator = penggunaTugas('pengelola-magang@example.com', ['kelola_tugas']);
    $magang = penggunaTugas('magang@example.com');
    $magang->update(['batch_id' => Batch::create(['nama' => 'Batch Juli', 'mulai' => '2026-07-01', 'selesai' => '2026-09-30'])->id]);
    $peran = PeranProduksi::create(['nama' => 'Fotografer', 'slug' => 'fotografer-tugas', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Pilih foto', 'status' => 'baru', 'dibuat_oleh' => $koordinator->id]);

    Livewire::actingAs($koordinator)
        ->test(KelolaTugas::class)
        ->call('aturPelaksana', $tugas->id)
        ->set('anggotaId', (string) $magang->id)
        ->set('peranId', (string) $peran->id)
        ->set('deadlinePenugasanAt', '2026-08-03T17:00')
        ->call('simpanPenugasan')
        ->assertHasErrors(['pembimbingId']);
});

it('pembimbing dapat membuka tugas dan menyimpan catatan tanpa mengubah pekerjaan', function () {
    $koordinator = penggunaTugas('koordinator-catatan@example.com', ['kelola_tugas']);
    $magang = penggunaTugas('magang-catatan@example.com');
    $pembimbing = penggunaTugas('pembimbing@example.com');
    $peran = PeranProduksi::create(['nama' => 'Penulis', 'slug' => 'penulis-catatan', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Latihan berita', 'status' => 'baru', 'dibuat_oleh' => $koordinator->id]);
    Penugasan::create(['user_id' => $magang->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'pembimbing_id' => $pembimbing->id, 'status' => 'aktif']);

    Livewire::actingAs($pembimbing)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->set('catatanPembimbingBaru', 'Perkuat lead dan pastikan kutipan akurat.')
        ->call('simpanCatatanPembimbing')
        ->assertHasNoErrors()
        ->call('tandaiSelesai')
        ->assertForbidden();

    expect(CatatanPembimbing::sole()->oleh_id)->toBe($pembimbing->id)
        ->and($tugas->fresh()->status)->toBe('baru');
});

it('tidak menampilkan catatan pembimbing pada halaman kerja', function () {
    $koordinator = penggunaTugas('koordinator-privasi-catatan@example.com', ['kelola_tugas']);
    $pelaksanaSatu = penggunaTugas('pelaksana-satu-catatan@example.com');
    $pelaksanaDua = penggunaTugas('pelaksana-dua-catatan@example.com');
    $pembimbingSatu = penggunaTugas('pembimbing-satu-catatan@example.com');
    $pembimbingDua = penggunaTugas('pembimbing-dua-catatan@example.com');
    $peran = PeranProduksi::create(['nama' => 'Penulis', 'slug' => 'penulis-privasi-catatan', 'aktif' => true]);
    $tugas = Tugas::create(['judul' => 'Latihan dengan dua pelaksana', 'status' => 'dikerjakan', 'dibuat_oleh' => $koordinator->id]);
    $penugasanSatu = Penugasan::create(['user_id' => $pelaksanaSatu->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'pembimbing_id' => $pembimbingSatu->id, 'status' => 'aktif']);
    $penugasanDua = Penugasan::create(['user_id' => $pelaksanaDua->id, 'tipe' => 'berdeadline', 'deadline_at' => now()->addDay(), 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'pembimbing_id' => $pembimbingDua->id, 'status' => 'aktif']);
    CatatanPembimbing::create(['penugasan_id' => $penugasanSatu->id, 'isi' => 'Catatan khusus pelaksana satu.', 'oleh_id' => $pembimbingSatu->id]);
    CatatanPembimbing::create(['penugasan_id' => $penugasanDua->id, 'isi' => 'Catatan khusus pelaksana dua.', 'oleh_id' => $pembimbingDua->id]);

    Livewire::actingAs($pelaksanaSatu)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->assertDontSee('Catatan khusus pelaksana satu.')
        ->assertDontSee('Catatan khusus pelaksana dua.');

    Livewire::actingAs($pembimbingDua)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->assertDontSee('Catatan khusus pelaksana dua.')
        ->assertDontSee('Catatan khusus pelaksana satu.');

    Livewire::actingAs($koordinator)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->assertDontSee('Catatan khusus pelaksana satu.')
        ->assertDontSee('Catatan khusus pelaksana dua.');
});
