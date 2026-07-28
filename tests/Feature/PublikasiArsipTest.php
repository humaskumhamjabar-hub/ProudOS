<?php

use App\Livewire\KelolaPublikasi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Publishing\Models\Kanal;
use Modules\Publishing\Models\Publikasi;

function penggunaPublikasi(bool $denganIzin): User
{
    $role = Role::create([
        'nama' => $denganIzin ? 'PIC Publikasi' : 'Staf Produksi',
        'slug' => $denganIzin ? 'pic-publikasi' : 'staf-produksi',
    ]);

    if ($denganIzin) {
        $izin = Permission::create(['nama' => 'Upload publikasi', 'slug' => 'upload_publikasi']);
        $role->permissions()->attach($izin);
    }

    return User::create([
        'nama' => $role->nama,
        'email' => $denganIzin ? 'pic-publikasi@example.com' : 'staf-produksi@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

function paketSiapTayang(User $user): PaketKonten
{
    return PaketKonten::create([
        'judul' => 'Layanan KI untuk Pelaku UMKM',
        'status' => 'review',
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ]);
}

it('membatasi meja publikasi lewat izin', function () {
    $this->actingAs(penggunaPublikasi(false))
        ->get(route('publikasi.index'))
        ->assertForbidden();
});

it('mewajibkan url sebelum paket dapat masuk arsip', function () {
    $user = penggunaPublikasi(true);
    $paket = paketSiapTayang($user);
    $kanal = Kanal::create(['nama' => 'Instagram Jabar', 'jenis' => 'instagram', 'aktif' => true]);

    Livewire::actingAs($user)
        ->test(KelolaPublikasi::class, ['paket' => $paket->id])
        ->set('kanalId', $kanal->id)
        ->set('tayangAt', '2026-08-25T15:30')
        ->set('url', '')
        ->call('simpanDanArsipkan')
        ->assertHasErrors(['url']);

    expect(Publikasi::count())->toBe(0)
        ->and($paket->fresh()->status)->toBe('review');
});

it('mencatat publikasi lengkap dan mengarsipkan paket secara atomik', function () {
    Storage::fake('public');
    $user = penggunaPublikasi(true);
    $paket = paketSiapTayang($user);
    $kanal = Kanal::create(['nama' => 'Website Kemenkum Jabar', 'jenis' => 'website', 'aktif' => true]);

    Livewire::actingAs($user)
        ->test(KelolaPublikasi::class, ['paket' => $paket->id])
        ->set('kanalId', $kanal->id)
        ->set('tayangAt', '2026-08-25T15:30')
        ->set('url', 'https://jabar.kemenkum.go.id/berita/layanan-ki-umkm')
        ->set('buktiTayang', UploadedFile::fake()->image('bukti-tayang.jpg'))
        ->call('simpanDanArsipkan')
        ->assertHasNoErrors();

    $publikasi = Publikasi::sole();

    expect($publikasi->paket_konten_id)->toBe($paket->id)
        ->and($publikasi->kanal_id)->toBe($kanal->id)
        ->and($publikasi->pic_id)->toBe($user->id)
        ->and($paket->fresh()->status)->toBe('arsip');

    Storage::disk('public')->assertExists($publikasi->evidence_path);
});

it('mencatat perubahan setelah tayang beserta alasan dan pemintanya', function () {
    $user = penggunaPublikasi(true);
    $paket = paketSiapTayang($user);
    $kanal = Kanal::create(['nama' => 'Instagram Jabar', 'jenis' => 'instagram', 'aktif' => true]);
    $publikasi = Publikasi::create([
        'paket_konten_id' => $paket->id,
        'kanal_id' => $kanal->id,
        'tayang_at' => '2026-08-25 15:30:00',
        'url' => 'https://instagram.com/p/contoh',
        'pic_id' => $user->id,
    ]);
    $paket->update(['status' => 'arsip']);

    Livewire::actingAs($user)
        ->test(KelolaPublikasi::class)
        ->call('editPublikasi', $publikasi->id)
        ->set('diubahSetelahTayang', true)
        ->set('alasanPerubahan', 'Koreksi nama pejabat pada caption.')
        ->set('dimintaOleh', 'Kepala Bagian Umum')
        ->call('simpanPerubahan')
        ->assertHasNoErrors();

    expect($publikasi->fresh()->diubah_setelah_tayang)->toBeTrue()
        ->and($publikasi->fresh()->alasan_perubahan)->toBe('Koreksi nama pejabat pada caption.')
        ->and($publikasi->fresh()->diminta_oleh)->toBe('Kepala Bagian Umum');
});

it('menolak penanda perubahan tayang tanpa alasan dan pihak yang meminta', function () {
    $user = penggunaPublikasi(true);
    $paket = paketSiapTayang($user);
    $kanal = Kanal::create(['nama' => 'Instagram Jabar', 'jenis' => 'instagram', 'aktif' => true]);
    $publikasi = Publikasi::create([
        'paket_konten_id' => $paket->id,
        'kanal_id' => $kanal->id,
        'tayang_at' => '2026-08-25 15:30:00',
        'url' => 'https://instagram.com/p/contoh',
        'pic_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(KelolaPublikasi::class)
        ->call('editPublikasi', $publikasi->id)
        ->set('diubahSetelahTayang', true)
        ->call('simpanPerubahan')
        ->assertHasErrors(['alasanPerubahan', 'dimintaOleh']);

    expect($publikasi->fresh()->diubah_setelah_tayang)->toBeFalse();
});
