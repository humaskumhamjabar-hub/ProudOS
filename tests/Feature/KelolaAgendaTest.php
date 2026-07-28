<?php

use App\Livewire\KelolaAgenda;
use Livewire\Livewire;
use Modules\Agenda\Models\Agenda;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;

function penggunaAgenda(bool $denganIzin): User
{
    $role = Role::create([
        'nama' => $denganIzin ? 'Koordinator' : 'Staf',
        'slug' => $denganIzin ? 'koordinator' : 'staf',
    ]);

    if ($denganIzin) {
        $izin = Permission::create(['nama' => 'Kelola agenda', 'slug' => 'kelola_agenda']);
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
