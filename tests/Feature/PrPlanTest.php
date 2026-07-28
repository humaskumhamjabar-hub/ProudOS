<?php

use App\Livewire\KelolaPrPlan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Agenda\Models\Agenda;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Planning\Models\JenisOutput;
use Modules\Planning\Models\PrPlan;
use Modules\Planning\Models\PrPlanItem;
use Modules\Publishing\Models\Kanal;

function penggunaPrPlan(bool $denganIzin): User
{
    $role = Role::create([
        'nama' => $denganIzin ? 'Koordinator PR' : 'Staf',
        'slug' => $denganIzin ? 'koordinator-pr' : 'staf-pr',
    ]);

    if ($denganIzin) {
        $izin = Permission::create(['nama' => 'Kelola PR Plan', 'slug' => 'kelola_pr_plan']);
        $role->permissions()->attach($izin);
    }

    return User::create([
        'nama' => $denganIzin ? 'Koordinator PR' : 'Staf PR',
        'email' => $denganIzin ? 'koordinator-pr@example.com' : 'staf-pr@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

it('membatasi halaman PR Plan lewat izin', function () {
    $this->actingAs(penggunaPrPlan(false))
        ->get(route('pr-plan.index'))
        ->assertForbidden();
});

it('membuat PR Plan beserta antrean konten tanpa tanggal pasti', function () {
    $user = penggunaPrPlan(true);
    $output = JenisOutput::create(['nama' => 'Berita web', 'slug' => 'berita-web', 'aktif' => true]);
    $kanal = Kanal::create(['nama' => 'Website Kanwil', 'jenis' => 'website', 'aktif' => true]);

    $komponen = Livewire::actingAs($user)
        ->test(KelolaPrPlan::class)
        ->call('buatPlan')
        ->set('nama', 'PR Plan Agustus 2026')
        ->set('tema', 'Pelayanan hukum semakin dekat')
        ->set('periodeMulai', '2026-08-01')
        ->set('periodeSelesai', '2026-08-31')
        ->set('targetJumlahKonten', 12)
        ->call('simpanPlan')
        ->assertHasNoErrors();

    $plan = PrPlan::sole();

    $komponen
        ->call('tambahItem', $plan->id)
        ->set('judulItem', 'Kenali layanan Administrasi Hukum Umum')
        ->set('catatanItem', 'Gunakan bahasa yang mudah dipahami masyarakat.')
        ->set('rencanaKasar', 'minggu ke-2 Agustus')
        ->set('jenisOutputId', $output->id)
        ->set('kanalTujuan', [$kanal->id])
        ->call('simpanItem')
        ->assertHasNoErrors();

    $item = PrPlanItem::sole();

    expect($plan->dibuat_oleh)->toBe($user->id)
        ->and($item->rencana_kasar)->toBe('minggu ke-2 Agustus')
        ->and($item->kanal_tujuan)->toBe([$kanal->id])
        ->and($item->status)->toBe('ide')
        ->and(Schema::hasColumn('pr_plan_items', 'tanggal'))->toBeFalse()
        ->and(Schema::hasColumn('pr_plan_items', 'mulai_at'))->toBeFalse();
});

it('menjadwalkan item dengan membuat Agenda sebagai satu sumber tanggal', function () {
    $user = penggunaPrPlan(true);
    $output = JenisOutput::create(['nama' => 'Carousel', 'slug' => 'carousel', 'aktif' => true]);
    $plan = PrPlan::create([
        'nama' => 'PR Plan Agustus',
        'periode_mulai' => '2026-08-01',
        'periode_selesai' => '2026-08-31',
        'target_jumlah_konten' => 8,
        'status' => 'berjalan',
        'dibuat_oleh' => $user->id,
    ]);
    $item = $plan->items()->create([
        'judul' => 'Hak Kekayaan Intelektual untuk UMKM',
        'rencana_kasar' => 'akhir Agustus',
        'jenis_output_id' => $output->id,
        'kanal_tujuan' => [],
        'status' => 'ide',
    ]);

    Livewire::actingAs($user)
        ->test(KelolaPrPlan::class)
        ->call('bukaJadwal', $item->id)
        ->set('agendaMulaiAt', '2026-08-25T09:00')
        ->set('agendaSelesaiAt', '2026-08-25T10:00')
        ->set('agendaLokasi', 'Studio Humas')
        ->call('jadwalkanItem')
        ->assertHasNoErrors();

    $agenda = Agenda::sole();
    $item->refresh();

    expect($item->status)->toBe('dijadwalkan')
        ->and($item->agenda_id)->toBe($agenda->id)
        ->and($agenda->judul)->toBe($item->judul)
        ->and($agenda->mulai_at->format('Y-m-d H:i'))->toBe('2026-08-25 09:00')
        ->and($agenda->sumber_type)->toBe('pr_plan_item')
        ->and($agenda->sumber_id)->toBe($item->id);
});
