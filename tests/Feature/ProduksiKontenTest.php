<?php

use App\Livewire\KelolaProduksiKonten;
use App\Livewire\KelolaPrPlan;
use Livewire\Livewire;
use Modules\Agenda\Models\Agenda;
use Modules\Content\Models\Draf;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Planning\Models\JenisOutput;
use Modules\Planning\Models\PrPlan;
use Modules\Planning\Models\PrPlanItem;

function penggunaProduksi(bool $denganIzin): User
{
    $role = Role::create([
        'nama' => $denganIzin ? 'Koordinator Konten' : 'Staf Konten',
        'slug' => $denganIzin ? 'koordinator-konten' : 'staf-konten',
    ]);

    if ($denganIzin) {
        $izin = Permission::create(['nama' => 'Kelola konten', 'slug' => 'kelola_konten']);
        $role->permissions()->attach($izin);
    }

    return User::create([
        'nama' => $denganIzin ? 'Koordinator Konten' : 'Staf Konten',
        'email' => $denganIzin ? 'koordinator-konten@example.com' : 'staf-konten@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

function itemPrPlanSiapProduksi(User $user): PrPlanItem
{
    $output = JenisOutput::create(['nama' => 'Berita web', 'slug' => 'berita-web', 'aktif' => true]);
    $plan = PrPlan::create([
        'nama' => 'PR Plan Agustus',
        'periode_mulai' => '2026-08-01',
        'periode_selesai' => '2026-08-31',
        'target_jumlah_konten' => 8,
        'status' => 'berjalan',
        'dibuat_oleh' => $user->id,
    ]);
    $agenda = Agenda::create([
        'judul' => 'Layanan KI untuk UMKM',
        'mulai_at' => '2026-08-25 09:00:00',
        'status' => 'rencana',
        'dibuat_oleh' => $user->id,
    ]);

    return $plan->items()->create([
        'judul' => 'Layanan KI untuk UMKM',
        'catatan' => 'Sorot manfaat perlindungan merek bagi pelaku usaha.',
        'rencana_kasar' => 'akhir Agustus',
        'jenis_output_id' => $output->id,
        'kanal_tujuan' => [],
        'agenda_id' => $agenda->id,
        'status' => 'dijadwalkan',
    ]);
}

it('membatasi meja produksi lewat izin', function () {
    $this->actingAs(penggunaProduksi(false))
        ->get(route('produksi.index'))
        ->assertForbidden();
});

it('memulai produksi dari item PR Plan tepat satu kali', function () {
    $user = penggunaProduksi(true);
    $item = itemPrPlanSiapProduksi($user);

    $komponen = Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class)
        ->call('mulaiDariPrPlan', $item->id)
        ->assertHasNoErrors();

    $paket = PaketKonten::sole();

    expect($paket->judul)->toBe($item->judul)
        ->and($paket->agenda_id)->toBe($item->agenda_id)
        ->and($paket->pr_plan_item_id)->toBe($item->id)
        ->and($paket->status)->toBe('on_progress')
        ->and($item->fresh()->status)->toBe('diproduksi');

    $komponen->call('mulaiDariPrPlan', $item->id);

    expect(PaketKonten::count())->toBe(1);
});

it('memulai produksi langsung dari layar PR Plan', function () {
    $user = penggunaProduksi(true);
    $izinPrPlan = Permission::create(['nama' => 'Kelola PR Plan', 'slug' => 'kelola_pr_plan']);
    $user->role->permissions()->attach($izinPrPlan);
    $item = itemPrPlanSiapProduksi($user);

    Livewire::actingAs($user)
        ->test(KelolaPrPlan::class)
        ->call('mulaiProduksi', $item->id);

    expect(PaketKonten::sole()->pr_plan_item_id)->toBe($item->id)
        ->and($item->fresh()->status)->toBe('diproduksi');
});

it('menyimpan draf manusia sebagai versi baru dan tidak menimpa versi lama', function () {
    $user = penggunaProduksi(true);
    $paket = PaketKonten::create([
        'judul' => 'Layanan KI untuk UMKM',
        'status' => 'on_progress',
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ]);

    $komponen = Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class)
        ->call('pilihPaket', $paket->id)
        ->set('jenisDraf', 'berita')
        ->set('isiDraf', 'Versi pertama berita layanan KI.')
        ->call('simpanDraf')
        ->assertHasNoErrors()
        ->set('isiDraf', 'Versi kedua yang sudah diperbaiki.')
        ->call('simpanDraf')
        ->assertHasNoErrors();

    expect(Draf::count())->toBe(2)
        ->and(Draf::orderBy('versi')->pluck('versi')->all())->toBe([1, 2])
        ->and(Draf::orderBy('versi')->pluck('asal')->unique()->all())->toBe(['manusia']);
});

it('memindahkan status papan dan mengembalikan revisi ke on progress', function () {
    $user = penggunaProduksi(true);
    $paket = PaketKonten::create([
        'judul' => 'Carousel Pelayanan Publik',
        'status' => 'on_progress',
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ]);

    $komponen = Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class)
        ->call('ubahStatus', $paket->id, 'finish_production')
        ->call('ubahStatus', $paket->id, 'review');

    expect($paket->fresh()->status)->toBe('review');

    $komponen->call('kembalikanRevisi', $paket->id);

    expect($paket->fresh()->status)->toBe('on_progress')
        ->and($paket->fresh()->revisi_ke)->toBe(1);
});
