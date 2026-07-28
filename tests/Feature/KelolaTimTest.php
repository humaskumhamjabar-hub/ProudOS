<?php

use App\Livewire\KelolaTim;
use Livewire\Livewire;
use Modules\People\Models\AksesLog;
use Modules\People\Models\Batch;
use Modules\People\Models\Ketidakhadiran;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;

function pengelolaTim(bool $denganIzin): User
{
    $role = Role::create([
        'nama' => $denganIzin ? 'Admin Tim' : 'Staf Tim',
        'slug' => $denganIzin ? 'admin-tim' : 'staf-tim',
    ]);

    if ($denganIzin) {
        $role->permissions()->attach(Permission::create(['nama' => 'Kelola pengguna', 'slug' => 'kelola_pengguna']));
    }

    return User::create([
        'nama' => $denganIzin ? 'Admin Tim' : 'Staf Tim',
        'email' => $denganIzin ? 'admin-tim@example.com' : 'staf-tim@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

it('membatasi kelola tim lewat izin', function () {
    $this->actingAs(pengelolaTim(false))
        ->get(route('tim.index'))
        ->assertForbidden();
});

it('membuat anggota beserta role dan izin tambahan', function () {
    $admin = pengelolaTim(true);
    $role = Role::create(['nama' => 'Staf', 'slug' => 'staf-kelola-tim']);
    $izin = Permission::create(['nama' => 'Upload publikasi', 'slug' => 'upload-publikasi-kelola-tim']);

    Livewire::actingAs($admin)
        ->test(KelolaTim::class)
        ->call('buatAnggota')
        ->set('nama', 'Dina Humas')
        ->set('email', 'dina@example.com')
        ->set('password', 'rahasia-sementara')
        ->set('roleId', $role->id)
        ->set('izinTambahan', [$izin->id])
        ->call('simpanAnggota')
        ->assertHasNoErrors();

    $anggota = User::where('email', 'dina@example.com')->sole();

    expect($anggota->nama)->toBe('Dina Humas')
        ->and($anggota->role_id)->toBe($role->id)
        ->and($anggota->izinTambahan()->pluck('permissions.id')->all())->toBe([$izin->id]);
});

it('mewajibkan batch dan masa akses untuk anggota magang', function () {
    $admin = pengelolaTim(true);
    $magang = Role::create(['nama' => 'Magang', 'slug' => 'magang']);
    $batch = Batch::create(['nama' => 'Batch Agustus', 'mulai' => '2026-08-01', 'selesai' => '2026-10-31']);

    $komponen = Livewire::actingAs($admin)
        ->test(KelolaTim::class)
        ->call('buatAnggota')
        ->set('nama', 'Raka Magang')
        ->set('email', 'raka@example.com')
        ->set('password', 'rahasia-sementara')
        ->set('roleId', $magang->id)
        ->call('simpanAnggota')
        ->assertHasErrors(['batchId', 'aktifMulai', 'aktifSampai']);

    $komponen
        ->set('batchId', $batch->id)
        ->set('aktifMulai', '2026-08-01')
        ->set('aktifSampai', '2026-10-31')
        ->call('simpanAnggota')
        ->assertHasNoErrors();

    expect(User::where('email', 'raka@example.com')->sole()->batch_id)->toBe($batch->id);
});

it('mencatat perubahan akhir masa akses anggota', function () {
    $admin = pengelolaTim(true);
    $role = Role::create(['nama' => 'Magang', 'slug' => 'magang']);
    $batch = Batch::create(['nama' => 'Batch Perpanjangan', 'mulai' => '2026-08-01', 'selesai' => '2026-10-31']);
    $anggota = User::create([
        'nama' => 'Ayu Magang',
        'email' => 'ayu@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'aktif_mulai' => '2026-08-01',
        'aktif_sampai' => '2026-09-30',
        'batch_id' => $batch->id,
        'status' => 'aktif',
    ]);

    Livewire::actingAs($admin)
        ->test(KelolaTim::class)
        ->call('editAnggota', $anggota->id)
        ->set('aktifSampai', '2026-10-31')
        ->set('alasanPerpanjangan', 'Program magang diperpanjang satu bulan.')
        ->call('simpanAnggota')
        ->assertHasNoErrors();

    $log = AksesLog::sole();

    expect($log->user_id)->toBe($anggota->id)
        ->and($log->aktif_sampai_lama->toDateString())->toBe('2026-09-30')
        ->and($log->aktif_sampai_baru->toDateString())->toBe('2026-10-31')
        ->and($log->oleh_id)->toBe($admin->id);
});

it('mengelola batch dan ketidakhadiran anggota', function () {
    $admin = pengelolaTim(true);
    $role = Role::create(['nama' => 'Staf', 'slug' => 'staf-absen']);
    $anggota = User::create([
        'nama' => 'Bagas Staf',
        'email' => 'bagas@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);

    $komponen = Livewire::actingAs($admin)
        ->test(KelolaTim::class)
        ->call('buatBatch')
        ->set('namaBatch', 'Magang Gelombang 9')
        ->set('batchMulai', '2026-09-01')
        ->set('batchSelesai', '2026-11-30')
        ->call('simpanBatch')
        ->assertHasNoErrors();

    $komponen
        ->call('catatKetidakhadiran', $anggota->id)
        ->set('jenisKetidakhadiran', 'izin')
        ->set('ketidakhadiranMulai', '2026-09-14')
        ->set('ketidakhadiranSelesai', '2026-09-15')
        ->set('catatanKetidakhadiran', 'Keperluan keluarga')
        ->call('simpanKetidakhadiran')
        ->assertHasNoErrors();

    expect(Batch::sole()->nama)->toBe('Magang Gelombang 9')
        ->and(Ketidakhadiran::sole()->user_id)->toBe($anggota->id)
        ->and(Ketidakhadiran::sole()->dicatat_oleh)->toBe($admin->id);
});
