<?php

use App\Livewire\Monitoring;
use Livewire\Livewire;
use Modules\Monitoring\Models\Temuan;
use Modules\Monitoring\Models\TindakLanjut;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;

function penggunaMonitoring(bool $diizinkan = true): User
{
    $role = Role::create(['nama' => 'Monitoring', 'slug' => 'monitoring-'.($diizinkan ? 'ya' : 'tidak')]);
    if ($diizinkan) {
        $izin = Permission::create(['nama' => 'Kelola monitoring', 'slug' => 'kelola_monitoring']);
        $role->permissions()->attach($izin);
    }

    return User::create(['nama' => 'Petugas Monitoring', 'email' => $diizinkan ? 'monitoring@example.com' : 'tanpa-monitoring@example.com', 'password' => 'password', 'role_id' => $role->id, 'status' => 'aktif']);
}

it('membatasi monitoring lewat gate', function () {
    $this->actingAs(penggunaMonitoring(false))->get(route('monitoring.index'))->assertForbidden();
});

it('menyimpan temuan dengan sentimen status dan pic', function () {
    $user = penggunaMonitoring();

    Livewire::actingAs($user)->test(Monitoring::class)
        ->call('buat')
        ->set('sumber', 'Instagram')
        ->set('ringkasan', 'Masyarakat menanyakan waktu layanan konsultasi hukum.')
        ->set('url', 'https://example.test/post/1')
        ->set('sentimen', 'netral')
        ->set('tanggal', '2026-07-28')
        ->set('picId', $user->id)
        ->call('simpan')
        ->assertHasNoErrors();

    $temuan = Temuan::sole();
    expect($temuan->status_tindak_lanjut)->toBe('baru')
        ->and($temuan->pic_id)->toBe($user->id)
        ->and($temuan->url)->toBe('https://example.test/post/1');
});

it('mencatat tindak lanjut dan menyelesaikan temuan', function () {
    $user = penggunaMonitoring();
    $temuan = Temuan::create(['sumber' => 'Berita daring', 'ringkasan' => 'Perlu klarifikasi data layanan.', 'sentimen' => 'negatif', 'tanggal' => '2026-07-28', 'status_tindak_lanjut' => 'baru', 'pic_id' => $user->id]);

    $komponen = Livewire::actingAs($user)->test(Monitoring::class)
        ->set("aksi.{$temuan->id}", 'Data telah dikonfirmasi kepada bidang terkait.')
        ->call('tambahTindakLanjut', $temuan->id)
        ->assertHasNoErrors();

    expect(TindakLanjut::sole()->oleh_id)->toBe($user->id)
        ->and($temuan->fresh()->status_tindak_lanjut)->toBe('diproses');

    $komponen->call('tandaiSelesai', $temuan->id);
    expect($temuan->fresh()->status_tindak_lanjut)->toBe('selesai');
});

it('menolak url yang tidak aman', function () {
    $user = penggunaMonitoring();
    Livewire::actingAs($user)->test(Monitoring::class)
        ->call('buat')->set('sumber', 'Pesan')->set('ringkasan', 'Ringkasan valid')
        ->set('tanggal', '2026-07-28')->set('url', 'javascript:alert(1)')
        ->call('simpan')->assertHasErrors(['url']);
    expect(Temuan::count())->toBe(0);
});
