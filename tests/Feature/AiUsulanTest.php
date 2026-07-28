<?php

use App\Livewire\KelolaProduksiKonten;
use Livewire\Livewire;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\HasilAi;
use Modules\Content\Models\AiUsulan;
use Modules\Content\Models\Bahan;
use Modules\Content\Models\Draf;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;

function penggunaAiUsulan(): User
{
    $role = Role::create(['nama' => 'Editor AI', 'slug' => 'editor-ai']);
    $izin = Permission::create(['nama' => 'Kelola konten', 'slug' => 'kelola_konten']);
    $role->permissions()->attach($izin);

    return User::create([
        'nama' => 'Editor AI',
        'email' => 'editor-ai@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

function paketAiUsulan(User $user): PaketKonten
{
    return PaketKonten::create([
        'judul' => 'Pelayanan hukum makin dekat',
        'status' => 'on_progress',
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ]);
}

function tambahSumberAi(PaketKonten $paket, User $user, string $isi = 'Kegiatan diikuti 120 pelaku UMKM Jawa Barat.'): Bahan
{
    return Bahan::create([
        'paket_konten_id' => $paket->id,
        'tipe' => 'catatan',
        'path' => '',
        'nama_asli' => 'Catatan produksi',
        'mime' => 'text/plain',
        'teks_terekstrak' => $isi,
        'status_ekstraksi' => 'selesai',
        'dipakai_final' => false,
        'diunggah_oleh' => $user->id,
        'urutan' => 1,
    ]);
}

function fakePenyediaAi(bool $tersedia = true): PenyediaAi
{
    return new class($tersedia) implements PenyediaAi
    {
        public array $permintaan = [];

        public function __construct(private readonly bool $aktif) {}

        public function tersedia(): bool
        {
            return $this->aktif;
        }

        public function hasilkan(string $jenis, string $judul, array $sumber): HasilAi
        {
            $this->permintaan = compact('jenis', 'judul', 'sumber');

            return new HasilAi(
                isi: 'Sebanyak 120 pelaku UMKM mengikuti layanan hukum terpadu.',
                model: 'fake-proud-1',
                promptVersi: 'konten-v1',
            );
        }
    };
}

it('menggunakan teks sumber dan hanya menyimpan hasil ke ai usulan', function () {
    $user = penggunaAiUsulan();
    $paket = paketAiUsulan($user);
    tambahSumberAi($paket, $user);
    $penyedia = fakePenyediaAi();
    app()->instance(PenyediaAi::class, $penyedia);

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->set('jenisUsulanAi', 'fakta')
        ->call('buatUsulanAi')
        ->assertHasNoErrors();

    $usulan = AiUsulan::sole();

    expect($penyedia->permintaan['judul'])->toBe($paket->judul)
        ->and($penyedia->permintaan['jenis'])->toBe('fakta')
        ->and($penyedia->permintaan['sumber'])->toContain('Kegiatan diikuti 120 pelaku UMKM Jawa Barat.')
        ->and($usulan->status)->toBe('menunggu')
        ->and($usulan->model)->toBe('fake-proud-1')
        ->and($usulan->prompt_versi)->toBe('konten-v1')
        ->and(Draf::count())->toBe(0);
});

it('menolak generasi saat belum ada teks sumber atau provider belum tersedia', function () {
    $user = penggunaAiUsulan();
    $paket = paketAiUsulan($user);
    app()->instance(PenyediaAi::class, fakePenyediaAi());

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->call('buatUsulanAi')
        ->assertHasErrors(['ai']);

    tambahSumberAi($paket, $user);
    app()->instance(PenyediaAi::class, fakePenyediaAi(false));

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->call('buatUsulanAi')
        ->assertHasErrors(['ai']);

    expect(AiUsulan::count())->toBe(0);
});

it('menangani kegagalan provider tanpa membuat usulan atau draf', function () {
    $user = penggunaAiUsulan();
    $paket = paketAiUsulan($user);
    tambahSumberAi($paket, $user);
    app()->instance(PenyediaAi::class, new class implements PenyediaAi
    {
        public function tersedia(): bool
        {
            return true;
        }

        public function hasilkan(string $jenis, string $judul, array $sumber): HasilAi
        {
            throw new RuntimeException('Provider sedang gagal.');
        }
    });

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->call('buatUsulanAi')
        ->assertHasErrors(['ai']);

    expect(AiUsulan::count())->toBe(0)
        ->and(Draf::count())->toBe(0);
});

it('mencatat keputusan terima dan tolak beserta peninjaunya', function () {
    $user = penggunaAiUsulan();
    $paket = paketAiUsulan($user);
    $diterima = $paket->aiUsulan()->create(['jenis' => 'berita', 'isi' => 'Usulan berita.', 'status' => 'menunggu']);
    $ditolak = $paket->aiUsulan()->create(['jenis' => 'caption', 'isi' => 'Usulan caption.', 'status' => 'menunggu']);

    $komponen = Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->call('terimaUsulanAi', $diterima->id)
        ->call('tolakUsulanAi', $ditolak->id)
        ->assertHasNoErrors();

    expect($diterima->fresh()->status)->toBe('diterima')
        ->and($diterima->fresh()->ditinjau_oleh)->toBe($user->id)
        ->and($diterima->fresh()->ditinjau_at)->not->toBeNull()
        ->and($ditolak->fresh()->status)->toBe('ditolak')
        ->and($ditolak->fresh()->ditinjau_oleh)->toBe($user->id)
        ->and($ditolak->fresh()->ditinjau_at)->not->toBeNull();
});

it('menandai usulan yang disunting dan tidak membuat draf otomatis', function () {
    $user = penggunaAiUsulan();
    $paket = paketAiUsulan($user);
    $usulan = $paket->aiUsulan()->create(['jenis' => 'caption', 'isi' => 'Teks awal AI.', 'status' => 'menunggu']);

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->call('mulaiEditUsulanAi', $usulan->id)
        ->set('isiEditUsulanAi', 'Teks hasil koreksi manusia.')
        ->call('simpanEditUsulanAi')
        ->assertHasNoErrors();

    expect($usulan->fresh()->isi)->toBe('Teks hasil koreksi manusia.')
        ->and($usulan->fresh()->status)->toBe('diedit')
        ->and($usulan->fresh()->ditinjau_oleh)->toBe($user->id)
        ->and(Draf::count())->toBe(0);
});

it('menyalin usulan yang sudah ditinjau ke editor namun tetap menunggu simpan manusia', function () {
    $user = penggunaAiUsulan();
    $paket = paketAiUsulan($user);
    $usulan = $paket->aiUsulan()->create([
        'jenis' => 'opsi_judul',
        'isi' => 'Layanan Hukum Hadir Lebih Dekat untuk UMKM',
        'status' => 'diterima',
        'ditinjau_oleh' => $user->id,
        'ditinjau_at' => now(),
    ]);

    $komponen = Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->call('gunakanUsulanAi', $usulan->id)
        ->assertSet('jenisDraf', 'judul')
        ->assertSet('isiDraf', 'Layanan Hukum Hadir Lebih Dekat untuk UMKM');

    expect(Draf::count())->toBe(0);

    $komponen->call('simpanDraf')->assertHasNoErrors();

    expect(Draf::sole()->asal)->toBe('manusia')
        ->and(Draf::sole()->isi)->toBe($usulan->isi);
});
