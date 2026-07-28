<?php

use App\Livewire\KelolaTugas;
use App\Livewire\KerjakanTugas;
use Livewire\Livewire;
use Modules\Content\Models\CatatanPembimbing;
use Modules\People\Models\Batch;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Scheduling\Models\Penugasan;
use Modules\Scheduling\Models\PeranProduksi;
use Modules\Work\Models\Tugas;

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
        ->assertHasNoErrors();

    expect($tugas->fresh()->status)->toBe('selesai')
        ->and($penugasan->fresh()->status)->toBe('selesai');
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

it('membatasi catatan pembimbing pada pelaksana dan pembimbing penugasan terkait', function () {
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
        ->assertSee('Catatan khusus pelaksana satu.')
        ->assertDontSee('Catatan khusus pelaksana dua.');

    Livewire::actingAs($pembimbingDua)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->assertSee('Catatan khusus pelaksana dua.')
        ->assertDontSee('Catatan khusus pelaksana satu.');

    Livewire::actingAs($koordinator)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->assertSee('Catatan khusus pelaksana satu.')
        ->assertSee('Catatan khusus pelaksana dua.');
});
