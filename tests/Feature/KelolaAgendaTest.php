<?php

use App\Livewire\KelolaAgenda;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Modules\Agenda\Models\Agenda;
use Modules\People\Models\Ketidakhadiran;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Scheduling\Models\Penugasan;
use Modules\Scheduling\Models\PeranProduksi;

function penggunaAgenda(bool $denganIzin, bool $denganIzinPenugasan = false): User
{
    $role = Role::create([
        'nama' => $denganIzin ? 'Koordinator' : 'Staf',
        'slug' => $denganIzin ? 'koordinator' : 'staf',
    ]);

    if ($denganIzin) {
        $izin = Permission::create(['nama' => 'Kelola agenda', 'slug' => 'kelola_agenda']);
        $role->permissions()->attach($izin);
    }

    if ($denganIzinPenugasan) {
        $izin = Permission::create(['nama' => 'Kelola penugasan', 'slug' => 'kelola_penugasan']);
        $role->permissions()->attach($izin);
    }

    return User::create([
        'nama' => $denganIzin ? 'Koordinator Agenda' : 'Staf Biasa',
        'email' => $denganIzin ? 'koordinator@example.com' : 'staf@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

it('hanya bisa dibuka oleh pengguna dengan izin kelola agenda', function () {
    $this->actingAs(penggunaAgenda(false))
        ->get(route('agenda.index'))
        ->assertForbidden();
});

it('menolak operasi penugasan tanpa izin kelola penugasan', function () {
    $agenda = Agenda::create([
        'judul' => 'Agenda terlarang',
        'mulai_at' => '2026-08-03 09:00:00',
        'selesai_at' => '2026-08-03 11:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => penggunaAgenda(true)->id,
    ]);

    Livewire::actingAs(User::first())
        ->test(KelolaAgenda::class)
        ->call('aturTim', $agenda->id)
        ->assertForbidden();
});

it('membuat agenda bertanggal dan berkebutuhan humas', function () {
    $user = penggunaAgenda(true);

    Livewire::actingAs($user)
        ->test(KelolaAgenda::class)
        ->call('buat')
        ->set('judul', 'Rapat koordinasi pelayanan publik')
        ->set('mulaiAt', '2026-08-03T09:00')
        ->set('selesaiAt', '2026-08-03T11:30')
        ->set('lokasi', 'Ruang Soepomo')
        ->set('kebutuhanHumas', ['foto', 'berita'])
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('formTerbuka', false);

    $agenda = Agenda::sole();

    expect($agenda->judul)->toBe('Rapat koordinasi pelayanan publik')
        ->and($agenda->mulai_at->format('Y-m-d H:i'))->toBe('2026-08-03 09:00')
        ->and($agenda->kebutuhan_humas)->toBe(['foto', 'berita'])
        ->and($agenda->dibuat_oleh)->toBe($user->id);
});

it('membuat penugasan agenda untuk anggota dan peran aktif', function () {
    $koordinator = penggunaAgenda(true, true);
    $anggota = penggunaAgenda(false);
    $peran = PeranProduksi::create(['nama' => 'Fotografer', 'slug' => 'fotografer', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Peliputan layanan publik',
        'mulai_at' => '2026-08-05 09:00:00',
        'selesai_at' => '2026-08-05 11:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $koordinator->id,
    ]);

    Livewire::actingAs($koordinator)
        ->test(KelolaAgenda::class)
        ->call('aturTim', $agenda->id)
        ->set('anggotaId', $anggota->id)
        ->set('peranId', $peran->id)
        ->call('simpanPenugasan')
        ->assertHasNoErrors();

    $penugasan = Penugasan::sole();

    expect($penugasan->user_id)->toBe($anggota->id)
        ->and($penugasan->peran_id)->toBe($peran->id)
        ->and($penugasan->tipe)->toBe('berjam')
        ->and($penugasan->mulai_at->format('Y-m-d H:i'))->toBe('2026-08-05 09:00')
        ->and($penugasan->selesai_at->format('Y-m-d H:i'))->toBe('2026-08-05 11:00')
        ->and($penugasan->untuk_type)->toBe('agenda')
        ->and($penugasan->untuk_id)->toBe($agenda->id)
        ->and($penugasan->status)->toBe('aktif');
});

it('menolak penugasan agenda tanpa waktu selesai', function () {
    $koordinator = penggunaAgenda(true, true);
    $anggota = penggunaAgenda(false);
    $peran = PeranProduksi::create(['nama' => 'Fotografer', 'slug' => 'fotografer', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Agenda terbuka',
        'mulai_at' => '2026-08-05 09:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $koordinator->id,
    ]);

    Livewire::actingAs($koordinator)
        ->test(KelolaAgenda::class)
        ->call('aturTim', $agenda->id)
        ->set('anggotaId', $anggota->id)
        ->set('peranId', $peran->id)
        ->call('simpanPenugasan')
        ->assertHasErrors(['agendaTimId']);

    expect(Penugasan::count())->toBe(0);
});

it('tidak membuka terobos ketika anggota terhalang ketidakhadiran atau masa akses', function (string $halangan) {
    $koordinator = penggunaAgenda(true, true);
    $anggota = penggunaAgenda(false);
    $peran = PeranProduksi::create(['nama' => 'Fotografer', 'slug' => 'fotografer', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Agenda bertembok',
        'mulai_at' => '2026-08-05 09:00:00',
        'selesai_at' => '2026-08-05 11:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $koordinator->id,
    ]);

    if ($halangan === 'ketidakhadiran') {
        Ketidakhadiran::create([
            'user_id' => $anggota->id,
            'jenis' => 'cuti',
            'mulai' => '2026-08-05',
            'selesai' => '2026-08-05',
            'dicatat_oleh' => $koordinator->id,
        ]);
    } else {
        $anggota->update(['aktif_sampai' => '2026-08-04']);
    }

    Livewire::actingAs($koordinator)
        ->test(KelolaAgenda::class)
        ->call('aturTim', $agenda->id)
        ->set('anggotaId', $anggota->id)
        ->set('peranId', $peran->id)
        ->call('simpanPenugasan')
        ->assertHasErrors(['user_id'])
        ->assertSet('bolehTerobos', false);

    expect(Penugasan::count())->toBe(0);
})->with(['ketidakhadiran', 'masa akses']);

it('meminta konfirmasi sebelum menerobos bentrok penugasan', function () {
    $koordinator = penggunaAgenda(true, true);
    $anggota = penggunaAgenda(false);
    $peran = PeranProduksi::create(['nama' => 'Fotografer', 'slug' => 'fotografer', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Agenda bentrok',
        'mulai_at' => '2026-08-05 09:00:00',
        'selesai_at' => '2026-08-05 11:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $koordinator->id,
    ]);
    Penugasan::create([
        'user_id' => $anggota->id,
        'peran_id' => $peran->id,
        'tipe' => 'berjam',
        'mulai_at' => '2026-08-05 08:00:00',
        'selesai_at' => '2026-08-05 10:00:00',
        'untuk_type' => 'agenda',
        'untuk_id' => $agenda->id,
        'status' => 'aktif',
    ]);

    Livewire::actingAs($koordinator)
        ->test(KelolaAgenda::class)
        ->call('aturTim', $agenda->id)
        ->set('anggotaId', $anggota->id)
        ->set('peranId', $peran->id)
        ->call('simpanPenugasan')
        ->assertHasErrors(['user_id'])
        ->assertSet('bolehTerobos', true);

    expect(Penugasan::count())->toBe(1);
});

it('tidak bisa melewati konfirmasi bentrok lewat parameter method publik', function () {
    $koordinator = penggunaAgenda(true, true);
    $anggota = penggunaAgenda(false);
    $peran = PeranProduksi::create(['nama' => 'Fotografer', 'slug' => 'fotografer', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Agenda bentrok',
        'mulai_at' => '2026-08-05 09:00:00',
        'selesai_at' => '2026-08-05 11:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $koordinator->id,
    ]);
    Penugasan::create([
        'user_id' => $anggota->id,
        'peran_id' => $peran->id,
        'tipe' => 'berjam',
        'mulai_at' => '2026-08-05 08:00:00',
        'selesai_at' => '2026-08-05 10:00:00',
        'untuk_type' => 'agenda',
        'untuk_id' => $agenda->id,
        'status' => 'aktif',
    ]);

    Livewire::actingAs($koordinator)
        ->test(KelolaAgenda::class)
        ->call('aturTim', $agenda->id)
        ->set('anggotaId', $anggota->id)
        ->set('peranId', $peran->id)
        ->call('simpanPenugasan', true)
        ->assertHasErrors(['user_id'])
        ->assertSet('bolehTerobos', true);

    expect(Penugasan::count())->toBe(1);
});

it('mengunci state konfirmasi terobos dari manipulasi client', function () {
    $koordinator = penggunaAgenda(true, true);

    expect(fn () => Livewire::actingAs($koordinator)
        ->test(KelolaAgenda::class)
        ->set('bolehTerobos', true))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('menerobos bentrok setelah dikonfirmasi dan mempertahankan jejak penggantian', function () {
    $koordinator = penggunaAgenda(true, true);
    $anggota = penggunaAgenda(false);
    $peran = PeranProduksi::create(['nama' => 'Fotografer', 'slug' => 'fotografer', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Agenda bentrok',
        'mulai_at' => '2026-08-05 09:00:00',
        'selesai_at' => '2026-08-05 11:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $koordinator->id,
    ]);
    $lama = Penugasan::create([
        'user_id' => $anggota->id,
        'peran_id' => $peran->id,
        'tipe' => 'berjam',
        'mulai_at' => '2026-08-05 08:00:00',
        'selesai_at' => '2026-08-05 10:00:00',
        'untuk_type' => 'agenda',
        'untuk_id' => $agenda->id,
        'status' => 'aktif',
    ]);

    $component = Livewire::actingAs($koordinator)
        ->test(KelolaAgenda::class)
        ->call('aturTim', $agenda->id)
        ->set('anggotaId', $anggota->id)
        ->set('peranId', $peran->id)
        ->call('simpanPenugasan')
        ->assertSet('bolehTerobos', true)
        ->call('terobosBentrok')
        ->assertHasNoErrors();

    $baru = Penugasan::whereKeyNot($lama->id)->sole();

    expect($lama->fresh()->status)->toBe('butuh_pengganti')
        ->and($baru->digantikan_dari_id)->toBe($lama->id)
        ->and($component->get('bolehTerobos'))->toBeFalse();
});

it('membatalkan penugasan hanya pada agenda yang sedang dibuka tanpa menghapusnya', function () {
    $koordinator = penggunaAgenda(true, true);
    $anggota = penggunaAgenda(false);
    $peran = PeranProduksi::create(['nama' => 'Fotografer', 'slug' => 'fotografer', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Agenda dibuka',
        'mulai_at' => '2026-08-05 09:00:00',
        'selesai_at' => '2026-08-05 11:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $koordinator->id,
    ]);
    $agendaLain = Agenda::create([
        'judul' => 'Agenda lain',
        'mulai_at' => '2026-08-06 09:00:00',
        'selesai_at' => '2026-08-06 11:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $koordinator->id,
    ]);
    $penugasan = Penugasan::create([
        'user_id' => $anggota->id,
        'peran_id' => $peran->id,
        'tipe' => 'berjam',
        'mulai_at' => $agenda->mulai_at,
        'selesai_at' => $agenda->selesai_at,
        'untuk_type' => 'agenda',
        'untuk_id' => $agenda->id,
        'status' => 'aktif',
    ]);
    $penugasanLain = Penugasan::create([
        'user_id' => $anggota->id,
        'peran_id' => $peran->id,
        'tipe' => 'berjam',
        'mulai_at' => $agendaLain->mulai_at,
        'selesai_at' => $agendaLain->selesai_at,
        'untuk_type' => 'agenda',
        'untuk_id' => $agendaLain->id,
        'status' => 'aktif',
    ]);

    $component = Livewire::actingAs($koordinator)
        ->test(KelolaAgenda::class)
        ->call('aturTim', $agenda->id)
        ->call('batalkanPenugasan', $penugasan->id)
        ->assertHasNoErrors();

    expect(fn () => $component->call('batalkanPenugasan', $penugasanLain->id))
        ->toThrow(ModelNotFoundException::class);

    expect($penugasan->fresh()->status)->toBe('batal')
        ->and($penugasanLain->fresh()->status)->toBe('aktif')
        ->and(Penugasan::count())->toBe(2);
});

it('mengatur ulang konfirmasi terobos ketika pilihan penugasan berubah', function (string $properti) {
    $koordinator = penggunaAgenda(true, true);
    $anggota = penggunaAgenda(false);
    $anggotaLain = User::create([
        'nama' => 'Staf Lain',
        'email' => 'staf-lain@example.com',
        'password' => 'password',
        'role_id' => $anggota->role_id,
        'status' => 'aktif',
    ]);
    $peran = PeranProduksi::create(['nama' => 'Fotografer', 'slug' => 'fotografer', 'aktif' => true]);
    $peranLain = PeranProduksi::create(['nama' => 'Videografer', 'slug' => 'videografer', 'aktif' => true]);
    $agenda = Agenda::create([
        'judul' => 'Agenda pertama',
        'mulai_at' => '2026-08-05 09:00:00',
        'selesai_at' => '2026-08-05 11:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $koordinator->id,
    ]);
    $agendaLain = Agenda::create([
        'judul' => 'Agenda kedua',
        'mulai_at' => '2026-08-06 09:00:00',
        'selesai_at' => '2026-08-06 11:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $koordinator->id,
    ]);
    Penugasan::create([
        'user_id' => $anggota->id,
        'peran_id' => $peran->id,
        'tipe' => 'berjam',
        'mulai_at' => '2026-08-05 08:00:00',
        'selesai_at' => '2026-08-05 10:00:00',
        'untuk_type' => 'agenda',
        'untuk_id' => $agenda->id,
        'status' => 'aktif',
    ]);

    $component = Livewire::actingAs($koordinator)
        ->test(KelolaAgenda::class)
        ->call('aturTim', $agenda->id)
        ->set('anggotaId', $anggota->id)
        ->set('peranId', $peran->id)
        ->call('simpanPenugasan')
        ->assertSet('bolehTerobos', true);

    match ($properti) {
        'agendaTimId' => $component->call('aturTim', $agendaLain->id),
        'anggotaId' => $component->set('anggotaId', $anggotaLain->id),
        'peranId' => $component->set('peranId', $peranLain->id),
    };

    $component->assertSet('bolehTerobos', false);
})->with([
    'agenda' => ['agendaTimId'],
    'anggota' => ['anggotaId'],
    'peran' => ['peranId'],
]);

it('menolak waktu selesai sebelum waktu mulai', function () {
    Livewire::actingAs(penggunaAgenda(true))
        ->test(KelolaAgenda::class)
        ->call('buat')
        ->set('judul', 'Agenda tidak valid')
        ->set('mulaiAt', '2026-08-03T11:00')
        ->set('selesaiAt', '2026-08-03T10:00')
        ->call('simpan')
        ->assertHasErrors(['selesaiAt']);

    expect(Agenda::count())->toBe(0);
});

it('memperbarui agenda tanpa mengubah pembuatnya', function () {
    $user = penggunaAgenda(true);
    $agenda = Agenda::create([
        'judul' => 'Judul awal',
        'mulai_at' => '2026-08-04 09:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(KelolaAgenda::class)
        ->call('edit', $agenda->id)
        ->set('judul', 'Judul diperbarui')
        ->set('status', 'berjalan')
        ->call('simpan')
        ->assertHasNoErrors();

    expect($agenda->fresh()->judul)->toBe('Judul diperbarui')
        ->and($agenda->fresh()->status)->toBe('berjalan')
        ->and($agenda->fresh()->dibuat_oleh)->toBe($user->id);
});
