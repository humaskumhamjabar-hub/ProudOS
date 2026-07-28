<?php

use App\Livewire\Pustaka;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Library\Models\Pustaka as DokumenPustaka;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;

function penggunaPustaka(bool $mengelola): User
{
    $role = Role::create(['nama' => $mengelola ? 'Pengelola Pustaka' : 'Pembaca', 'slug' => $mengelola ? 'pengelola-pustaka' : 'pembaca-pustaka']);
    if ($mengelola) {
        $role->permissions()->attach(Permission::create(['nama' => 'Kelola pustaka', 'slug' => 'kelola_pustaka']));
    }

    return User::create(['nama' => $role->nama, 'email' => $role->slug.'@example.com', 'password' => 'password', 'role_id' => $role->id, 'status' => 'aktif']);
}

it('semua pengguna login dapat membaca dan mencari pustaka', function () {
    $admin = penggunaPustaka(true);
    $pembaca = penggunaPustaka(false);
    DokumenPustaka::create(['judul' => 'SOP Liputan', 'kategori' => 'sop', 'tipe' => 'teks', 'isi' => 'Pastikan surat tugas tersedia.', 'versi' => 1, 'dibuat_oleh' => $admin->id]);
    DokumenPustaka::create(['judul' => 'Pedoman Caption', 'kategori' => 'pedoman', 'tipe' => 'teks', 'isi' => 'Gunakan bahasa publik.', 'versi' => 1, 'dibuat_oleh' => $admin->id]);

    Livewire::actingAs($pembaca)->test(Pustaka::class)
        ->assertSee('SOP Liputan')
        ->assertSee('Pedoman Caption')
        ->set('cari', 'surat tugas')
        ->assertSee('SOP Liputan')
        ->assertDontSee('Pedoman Caption')
        ->call('buat')
        ->assertForbidden();
});

it('pengelola membuat teks dan pembaruan menaikkan versi', function () {
    $admin = penggunaPustaka(true);

    $halaman = Livewire::actingAs($admin)->test(Pustaka::class)
        ->call('buat')
        ->set('judul', 'Panduan Foto Kegiatan')
        ->set('kategori', 'pedoman')
        ->set('tipe', 'teks')
        ->set('isi', 'Ambil foto utama dan detail layanan.')
        ->call('simpan')
        ->assertHasNoErrors();

    $dokumen = DokumenPustaka::sole();
    $halaman->call('edit', $dokumen->id)->set('isi', 'Ambil foto utama, detail layanan, dan suasana.')->call('simpan')->assertHasNoErrors();

    expect($dokumen->fresh()->versi)->toBe(2);
});

it('pengelola mengunggah berkas tanpa mengekspos path penyimpanan', function () {
    Storage::fake('local');
    $admin = penggunaPustaka(true);

    Livewire::actingAs($admin)->test(Pustaka::class)
        ->call('buat')
        ->set('judul', 'Template Laporan')
        ->set('kategori', 'template')
        ->set('tipe', 'file')
        ->set('berkas', UploadedFile::fake()->create('template.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
        ->call('simpan')
        ->assertHasNoErrors();

    $dokumen = DokumenPustaka::sole();
    Storage::disk('local')->assertExists($dokumen->path);
    expect($dokumen->path)->toStartWith('pustaka/');
});

it('baru menghapus berkas lama setelah metadata pengganti tersimpan', function () {
    Storage::fake('local');
    $admin = penggunaPustaka(true);
    Storage::disk('local')->put('pustaka/lama.pdf', 'lama');
    $dokumen = DokumenPustaka::create([
        'judul' => 'Dokumen lama',
        'kategori' => 'sop',
        'tipe' => 'file',
        'path' => 'pustaka/lama.pdf',
        'versi' => 1,
        'dibuat_oleh' => $admin->id,
    ]);

    DokumenPustaka::updating(fn () => throw new RuntimeException('database gagal'));

    expect(fn () => Livewire::actingAs($admin)->test(Pustaka::class)
        ->call('edit', $dokumen->id)
        ->set('berkas', UploadedFile::fake()->create('baru.pdf', 20, 'application/pdf'))
        ->call('simpan'))
        ->toThrow(RuntimeException::class, 'database gagal');

    Storage::disk('local')->assertExists('pustaka/lama.pdf');
    expect(Storage::disk('local')->allFiles('pustaka'))->toBe(['pustaka/lama.pdf'])
        ->and($dokumen->fresh()->path)->toBe('pustaka/lama.pdf');
});

it('menghapus berkas lama setelah penggantian berkas berhasil disimpan', function () {
    Storage::fake('local');
    $admin = penggunaPustaka(true);
    Storage::disk('local')->put('pustaka/lama.pdf', 'lama');
    $dokumen = DokumenPustaka::create([
        'judul' => 'Dokumen lama',
        'kategori' => 'sop',
        'tipe' => 'file',
        'path' => 'pustaka/lama.pdf',
        'versi' => 1,
        'dibuat_oleh' => $admin->id,
    ]);

    Livewire::actingAs($admin)->test(Pustaka::class)
        ->call('edit', $dokumen->id)
        ->set('berkas', UploadedFile::fake()->create('baru.pdf', 20, 'application/pdf'))
        ->call('simpan')
        ->assertHasNoErrors();

    $pathBaru = $dokumen->fresh()->path;
    Storage::disk('local')->assertMissing('pustaka/lama.pdf');
    Storage::disk('local')->assertExists($pathBaru);
});

it('menghapus berkas lama setelah dokumen diubah menjadi teks', function () {
    Storage::fake('local');
    $admin = penggunaPustaka(true);
    Storage::disk('local')->put('pustaka/lama.pdf', 'lama');
    $dokumen = DokumenPustaka::create([
        'judul' => 'Dokumen lama',
        'kategori' => 'sop',
        'tipe' => 'file',
        'path' => 'pustaka/lama.pdf',
        'versi' => 1,
        'dibuat_oleh' => $admin->id,
    ]);

    Livewire::actingAs($admin)->test(Pustaka::class)
        ->call('edit', $dokumen->id)
        ->set('tipe', 'teks')
        ->set('isi', 'Dokumen kini tersedia sebagai teks.')
        ->call('simpan')
        ->assertHasNoErrors();

    Storage::disk('local')->assertMissing('pustaka/lama.pdf');
    expect($dokumen->fresh()->path)->toBeNull()
        ->and($dokumen->fresh()->isi)->toBe('Dokumen kini tersedia sebagai teks.');
});
