<?php

use App\Livewire\Kalender;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Modules\Agenda\Models\Agenda;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;

it('pengelola agenda dapat mencetak jadwal harian PDF', function () {
    Carbon::setTestNow('2026-07-28 08:00:00');
    $role = Role::create(['nama' => 'Koordinator Kalender', 'slug' => 'koordinator-kalender']);
    $role->permissions()->attach(Permission::create(['nama' => 'Kelola agenda', 'slug' => 'kelola_agenda']));
    $user = User::create(['nama' => 'Koordinator Kalender', 'email' => 'kalender@example.com', 'password' => 'password', 'role_id' => $role->id, 'status' => 'aktif']);
    Agenda::create(['judul' => 'Briefing pagi', 'mulai_at' => '2026-07-28 09:00:00', 'selesai_at' => '2026-07-28 10:00:00', 'status' => 'rencana', 'dibuat_oleh' => $user->id]);

    Livewire::actingAs($user)
        ->test(Kalender::class)
        ->call('unduhJadwalHarian')
        ->assertFileDownloaded('jadwal-harian-2026-07-28.pdf');
});
