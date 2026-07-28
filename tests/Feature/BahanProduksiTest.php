<?php

use App\Livewire\KelolaProduksiKonten;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Content\Models\Bahan;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;

function penggunaBahan(): User
{
    $role = Role::create(['nama' => 'Produser Konten', 'slug' => 'produser-konten']);
    $izin = Permission::create(['nama' => 'Kelola konten', 'slug' => 'kelola_konten']);
    $role->permissions()->attach($izin);

    return User::create([
        'nama' => 'Produser Konten',
        'email' => 'produser-bahan@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

function paketBahan(User $user): PaketKonten
{
    return PaketKonten::create([
        'judul' => 'Pelayanan AHU di Jawa Barat',
        'status' => 'on_progress',
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ]);
}

it('mengunggah beberapa bahan ke paket aktif dengan urutan berlanjut', function () {
    Storage::fake('local');
    $user = penggunaBahan();
    $paket = paketBahan($user);

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->set('tipeBahan', 'foto')
        ->set('unggahanBahan', [
            UploadedFile::fake()->image('suasana-kegiatan.jpg'),
            UploadedFile::fake()->image('narasumber.png'),
        ])
        ->call('unggahBahan')
        ->assertHasNoErrors();

    expect(Bahan::count())->toBe(2)
        ->and(Bahan::orderBy('urutan')->pluck('urutan')->all())->toBe([1, 2])
        ->and(Bahan::pluck('diunggah_oleh')->unique()->all())->toBe([$user->id]);

    Bahan::each(fn (Bahan $bahan) => Storage::disk('local')->assertExists($bahan->path));
});

it('menyimpan catatan sebagai bahan teks tanpa file fisik', function () {
    Storage::fake('local');
    $user = penggunaBahan();
    $paket = paketBahan($user);

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->set('tipeBahan', 'catatan')
        ->set('catatanBahan', 'Pesan utama: layanan AHU harus mudah dipahami masyarakat.')
        ->call('unggahBahan')
        ->assertHasNoErrors();

    $bahan = Bahan::sole();

    expect($bahan->tipe)->toBe('catatan')
        ->and($bahan->teks_terekstrak)->toBe('Pesan utama: layanan AHU harus mudah dipahami masyarakat.')
        ->and($bahan->status_ekstraksi)->toBe('selesai')
        ->and($bahan->path)->toBe('');
});

it('hanya mengizinkan foto ditandai sebagai bahan final', function () {
    $user = penggunaBahan();
    $paket = paketBahan($user);
    $foto = Bahan::create([
        'paket_konten_id' => $paket->id,
        'tipe' => 'foto',
        'path' => 'bahan/1/foto.jpg',
        'nama_asli' => 'foto.jpg',
        'mime' => 'image/jpeg',
        'status_ekstraksi' => 'menunggu',
        'dipakai_final' => false,
        'diunggah_oleh' => $user->id,
        'urutan' => 1,
    ]);
    $dokumen = Bahan::create([
        'paket_konten_id' => $paket->id,
        'tipe' => 'dokumen',
        'path' => 'bahan/1/sambutan.pdf',
        'nama_asli' => 'sambutan.pdf',
        'mime' => 'application/pdf',
        'status_ekstraksi' => 'menunggu',
        'dipakai_final' => false,
        'diunggah_oleh' => $user->id,
        'urutan' => 2,
    ]);

    $komponen = Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->call('toggleDipakaiFinal', $foto->id)
        ->assertHasNoErrors();

    expect($foto->fresh()->dipakai_final)->toBeTrue();

    $komponen->call('toggleDipakaiFinal', $dokumen->id)->assertStatus(422);
    expect($dokumen->fresh()->dipakai_final)->toBeFalse();
});

it('mengurutkan bahan di dalam paket tanpa menyentuh paket lain', function () {
    $user = penggunaBahan();
    $paket = paketBahan($user);
    $paketLain = PaketKonten::create([
        'judul' => 'Paket lain',
        'status' => 'on_progress',
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ]);
    $pertama = Bahan::create(['paket_konten_id' => $paket->id, 'tipe' => 'foto', 'path' => 'a.jpg', 'nama_asli' => 'a.jpg', 'status_ekstraksi' => 'menunggu', 'diunggah_oleh' => $user->id, 'urutan' => 1]);
    $kedua = Bahan::create(['paket_konten_id' => $paket->id, 'tipe' => 'foto', 'path' => 'b.jpg', 'nama_asli' => 'b.jpg', 'status_ekstraksi' => 'menunggu', 'diunggah_oleh' => $user->id, 'urutan' => 2]);
    $asing = Bahan::create(['paket_konten_id' => $paketLain->id, 'tipe' => 'foto', 'path' => 'c.jpg', 'nama_asli' => 'c.jpg', 'status_ekstraksi' => 'menunggu', 'diunggah_oleh' => $user->id, 'urutan' => 1]);

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->call('urutkanBahan', [$kedua->id, $pertama->id])
        ->assertHasNoErrors();

    expect($kedua->fresh()->urutan)->toBe(1)
        ->and($pertama->fresh()->urutan)->toBe(2)
        ->and($asing->fresh()->urutan)->toBe(1);
});

it('menghapus record dan file bahan milik paket aktif', function () {
    Storage::fake('local');
    $user = penggunaBahan();
    $paket = paketBahan($user);
    Storage::disk('local')->put('bahan/1/foto.jpg', 'gambar');
    $bahan = Bahan::create([
        'paket_konten_id' => $paket->id,
        'tipe' => 'foto',
        'path' => 'bahan/1/foto.jpg',
        'nama_asli' => 'foto.jpg',
        'mime' => 'image/jpeg',
        'status_ekstraksi' => 'menunggu',
        'diunggah_oleh' => $user->id,
        'urutan' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->call('hapusBahan', $bahan->id)
        ->assertHasNoErrors();

    expect(Bahan::count())->toBe(0);
    Storage::disk('local')->assertMissing('bahan/1/foto.jpg');
});
