<?php

use App\Livewire\KelolaProduksiKonten;
use App\Livewire\KelolaPublikasi;
use App\Livewire\KerjakanTugas;
use App\Livewire\PapanKanban;
use App\Livewire\TugasSaya;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Agenda\Models\Agenda;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Planning\Models\JenisOutput;
use Modules\Planning\Models\PrPlan;
use Modules\Publishing\Models\Kanal;
use Modules\Publishing\Models\Publikasi;
use Modules\Scheduling\Models\Penugasan;
use Modules\Scheduling\Models\PeranProduksi;
use Modules\Work\Models\Tugas;

function penggunaAlurTerpadu(): User
{
    $role = Role::create(['nama' => 'Produser Terpadu', 'slug' => 'produser-terpadu']);

    foreach ([
        ['Kelola konten', 'kelola_konten'],
        ['Upload publikasi', 'upload_publikasi'],
    ] as [$nama, $slug]) {
        $role->permissions()->attach(Permission::create(['nama' => $nama, 'slug' => $slug]));
    }

    return User::create([
        'nama' => 'Produser Terpadu',
        'email' => 'produser-terpadu@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

function paketUntukPapan(User $user, string $judul, string $status, array $tambahan = []): PaketKonten
{
    return PaketKonten::create(array_merge([
        'judul' => $judul,
        'status' => $status,
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ], $tambahan));
}

it('papan memakai paket konten sebagai satu-satunya sumber status', function () {
    $user = penggunaAlurTerpadu();
    paketUntukPapan($user, 'Paket sedang ditulis', 'on_progress');
    paketUntukPapan($user, 'Paket selesai produksi', 'finish_production');
    paketUntukPapan($user, 'Paket menunggu publikasi', 'review');
    paketUntukPapan($user, 'Paket sudah arsip', 'arsip');
    Tugas::create(['judul' => 'Tugas lama tidak boleh muncul', 'status' => 'dikerjakan', 'dibuat_oleh' => $user->id]);

    Livewire::actingAs($user)
        ->test(PapanKanban::class)
        ->assertSee('Paket sedang ditulis')
        ->assertSee('Paket selesai produksi')
        ->assertSee('Paket menunggu publikasi')
        ->assertDontSee('Paket sudah arsip')
        ->assertDontSee('Tugas lama tidak boleh muncul')
        ->assertViewHas('kolom', fn (array $kolom) => $kolom['on_progress']->count() === 1
            && $kolom['finish_production']->count() === 1
            && $kolom['review']->count() === 1);
});

it('papan dapat difilter menurut orang yang ditugaskan dan sumber paket', function () {
    $user = penggunaAlurTerpadu();
    $orangLain = User::create(['nama' => 'Editor Lain', 'email' => 'editor-lain@example.com', 'password' => 'password', 'role_id' => $user->role_id, 'status' => 'aktif']);
    $peran = PeranProduksi::create(['nama' => 'Editor', 'slug' => 'editor-terpadu', 'aktif' => true]);
    $agenda = Agenda::create(['judul' => 'Agenda Liputan', 'mulai_at' => '2026-07-30 09:00:00', 'status' => 'rencana', 'dibuat_oleh' => $user->id]);
    $paketAgenda = paketUntukPapan($user, 'Paket agenda', 'on_progress', ['agenda_id' => $agenda->id]);
    $paketPrPlan = paketUntukPapan($user, 'Paket PR Plan', 'on_progress', ['pr_plan_item_id' => 99]);
    Penugasan::create(['user_id' => $user->id, 'tipe' => 'berdeadline', 'deadline_at' => '2026-08-01 17:00:00', 'untuk_type' => 'paket_konten', 'untuk_id' => $paketAgenda->id, 'peran_id' => $peran->id, 'status' => 'aktif']);
    Penugasan::create(['user_id' => $orangLain->id, 'tipe' => 'berdeadline', 'deadline_at' => '2026-08-02 17:00:00', 'untuk_type' => 'paket_konten', 'untuk_id' => $paketPrPlan->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    Livewire::actingAs($user)
        ->test(PapanKanban::class)
        ->set('filterOrang', (string) $user->id)
        ->set('filterSumber', 'agenda')
        ->assertSee('Paket agenda')
        ->assertDontSee('Paket PR Plan');
});

it('tugas dengan subjek paket konten langsung membuka meja produksi dan menandai dibaca', function () {
    $user = penggunaAlurTerpadu();
    $paket = paketUntukPapan($user, 'Paket yang dikerjakan', 'on_progress');
    $tugas = Tugas::create([
        'judul' => 'Tulis berita paket',
        'status' => 'baru',
        'subjek_type' => 'paket_konten',
        'subjek_id' => $paket->id,
        'dibuat_oleh' => $user->id,
    ]);
    $peran = PeranProduksi::create(['nama' => 'Penulis', 'slug' => 'penulis-terpadu', 'aktif' => true]);
    $penugasan = Penugasan::create(['user_id' => $user->id, 'tipe' => 'berdeadline', 'deadline_at' => '2026-08-01 17:00:00', 'untuk_type' => 'tugas', 'untuk_id' => $tugas->id, 'peran_id' => $peran->id, 'status' => 'aktif']);

    Livewire::actingAs($user)
        ->test(KerjakanTugas::class, ['tugasId' => $tugas->id])
        ->assertRedirect(route('produksi.index', ['paket' => $paket->id]));

    expect($penugasan->fresh()->dibaca_at)->not->toBeNull();
});

it('penugasan langsung ke paket konten membuka meja produksi dari tugas saya dan beranda', function () {
    $user = penggunaAlurTerpadu();
    $paket = paketUntukPapan($user, 'Paket langsung untuk editor', 'on_progress');
    $peran = PeranProduksi::create(['nama' => 'Editor', 'slug' => 'editor-langsung', 'aktif' => true]);
    Penugasan::create([
        'user_id' => $user->id,
        'tipe' => 'berdeadline',
        'deadline_at' => now()->addDay(),
        'untuk_type' => 'paket_konten',
        'untuk_id' => $paket->id,
        'peran_id' => $peran->id,
        'status' => 'aktif',
    ]);
    $urlProduksi = route('produksi.index', ['paket' => $paket->id]);

    Livewire::actingAs($user)
        ->test(TugasSaya::class)
        ->assertSee($paket->judul)
        ->assertSeeHtml('href="'.$urlProduksi.'"');

    $this->actingAs($user)
        ->get(route('beranda'))
        ->assertOk()
        ->assertSee('Editor')
        ->assertSee('href="'.$urlProduksi.'"', escape: false);
});

it('menyelesaikan alur PR Plan hingga publikasi dan arsip pada satu paket', function () {
    Storage::fake('public');
    $user = penggunaAlurTerpadu();
    $jenis = JenisOutput::create(['nama' => 'Berita web', 'slug' => 'berita-terpadu', 'aktif' => true]);
    $plan = PrPlan::create(['nama' => 'PR Plan Terpadu', 'periode_mulai' => '2026-07-01', 'periode_selesai' => '2026-07-31', 'target_jumlah_konten' => 1, 'status' => 'berjalan', 'dibuat_oleh' => $user->id]);
    $agenda = Agenda::create(['judul' => 'Pelayanan Publik', 'mulai_at' => '2026-07-30 09:00:00', 'status' => 'rencana', 'dibuat_oleh' => $user->id]);
    $item = $plan->items()->create(['judul' => 'Pelayanan Publik', 'jenis_output_id' => $jenis->id, 'kanal_tujuan' => [], 'agenda_id' => $agenda->id, 'status' => 'dijadwalkan']);

    $produksi = Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class)
        ->call('mulaiDariPrPlan', $item->id);
    $paket = PaketKonten::sole();
    $produksi
        ->call('ubahStatus', $paket->id, 'finish_production')
        ->call('ubahStatus', $paket->id, 'review');

    $kanal = Kanal::create(['nama' => 'Website Kanwil', 'jenis' => 'website', 'aktif' => true]);
    Livewire::actingAs($user)
        ->test(KelolaPublikasi::class, ['paket' => $paket->id])
        ->set('kanalId', $kanal->id)
        ->set('tayangAt', '2026-07-31T10:00')
        ->set('url', 'https://example.test/pelayanan-publik')
        ->set('buktiTayang', UploadedFile::fake()->image('bukti.png'))
        ->call('simpanDanArsipkan')
        ->assertHasNoErrors();

    expect($item->fresh()->status)->toBe('diproduksi')
        ->and($paket->fresh()->status)->toBe('arsip')
        ->and(Publikasi::sole()->paket_konten_id)->toBe($paket->id)
        ->and(Publikasi::sole()->url)->toBe('https://example.test/pelayanan-publik');
});
