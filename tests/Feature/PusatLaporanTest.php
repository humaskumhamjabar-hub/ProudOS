<?php

use App\Livewire\PusatLaporan;
use Livewire\Livewire;
use Modules\Agenda\Models\Agenda;
use Modules\Content\Models\CatatanPembimbing;
use Modules\Content\Models\Draf;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Batch;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Planning\Models\JenisOutput;
use Modules\Planning\Models\PrPlan;
use Modules\Publishing\Models\Kanal;
use Modules\Publishing\Models\Publikasi;
use Modules\Scheduling\Models\Penugasan;
use Modules\Scheduling\Models\PeranProduksi;

function penggunaLaporan(bool $denganIzin = true): User
{
    $role = Role::create(['nama' => 'Koordinator Laporan', 'slug' => 'koordinator-laporan']);

    if ($denganIzin) {
        $izin = Permission::create(['nama' => 'Lihat laporan', 'slug' => 'lihat_laporan']);
        $role->permissions()->attach($izin);
    }

    return User::create([
        'nama' => 'Koordinator Laporan',
        'email' => $denganIzin ? 'laporan@example.com' : 'tanpa-laporan@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

function dataEvaluasiPrPlan(User $user): array
{
    $jenis = JenisOutput::create(['nama' => 'Berita', 'slug' => 'berita', 'aktif' => true]);
    $plan = PrPlan::create([
        'nama' => 'PR Plan Juli',
        'periode_mulai' => '2026-07-01',
        'periode_selesai' => '2026-07-31',
        'target_jumlah_konten' => 4,
        'status' => 'berjalan',
        'dibuat_oleh' => $user->id,
    ]);

    foreach ([
        ['Tayang pertama', 'diproduksi'],
        ['Tayang kedua', 'diproduksi'],
        ['Belum dikerjakan', 'ide'],
        ['Dibatalkan', 'batal'],
    ] as [$judul, $status]) {
        $plan->items()->create([
            'judul' => $judul,
            'jenis_output_id' => $jenis->id,
            'kanal_tujuan' => [],
            'status' => $status,
        ]);
    }

    $kanal = Kanal::create(['nama' => 'Instagram', 'jenis' => 'instagram', 'aktif' => true]);
    $paketSatu = PaketKonten::create(['judul' => 'Tayang pertama', 'pr_plan_item_id' => $plan->items()->first()->id, 'status' => 'arsip', 'revisi_ke' => 0, 'dibuat_oleh' => $user->id]);
    $paketDua = PaketKonten::create(['judul' => 'Tayang kedua', 'pr_plan_item_id' => $plan->items()->skip(1)->first()->id, 'status' => 'review', 'revisi_ke' => 0, 'dibuat_oleh' => $user->id]);
    Publikasi::create(['paket_konten_id' => $paketSatu->id, 'kanal_id' => $kanal->id, 'tayang_at' => '2026-07-10 10:00:00', 'url' => 'https://example.test/1', 'pic_id' => $user->id]);

    return compact('plan', 'kanal', 'paketSatu', 'paketDua');
}

it('membatasi pusat laporan lewat gate', function () {
    $this->actingAs(penggunaLaporan(false))
        ->get(route('laporan.index'))
        ->assertForbidden();
});

it('menghitung target, realisasi tayang, pipeline, dan kekurangan PR Plan', function () {
    $user = penggunaLaporan();
    ['plan' => $plan] = dataEvaluasiPrPlan($user);

    Livewire::actingAs($user)
        ->test(PusatLaporan::class)
        ->set('tab', 'pr-plan')
        ->set('prPlanId', $plan->id)
        ->assertViewHas('evaluasiPrPlan', function (array $evaluasi) {
            return $evaluasi['target'] === 4
                && $evaluasi['realisasi'] === 1
                && $evaluasi['pipeline'] === 1
                && $evaluasi['belum_dikerjakan'] === 1
                && $evaluasi['batal'] === 1
                && $evaluasi['kekurangan'] === 3
                && $evaluasi['persentase'] === 25.0;
        });
});

it('memfilter laporan publikasi berdasarkan periode dan kanal', function () {
    $user = penggunaLaporan();
    ['kanal' => $instagram] = dataEvaluasiPrPlan($user);
    $website = Kanal::create(['nama' => 'Website', 'jenis' => 'website', 'aktif' => true]);
    Publikasi::create(['kanal_id' => $website->id, 'tayang_at' => '2026-08-01 10:00:00', 'url' => 'https://example.test/aug', 'pic_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(PusatLaporan::class)
        ->set('mulai', '2026-07-01')
        ->set('selesai', '2026-07-31')
        ->set('kanalId', $instagram->id)
        ->assertViewHas('publikasi', fn ($publikasi) => $publikasi->count() === 1 && $publikasi->first()->kanal_id === $instagram->id)
        ->assertViewHas('ringkasanPublikasi', fn (array $ringkasan) => $ringkasan['total'] === 1 && $ringkasan['kanal'] === ['Instagram' => 1]);
});

it('menyusun rekap magang per peran, pembimbing, kegiatan, karya latihan, dan catatan', function () {
    $user = penggunaLaporan();
    $batch = Batch::create(['nama' => 'Magang Batch 7', 'mulai' => '2026-07-01', 'selesai' => '2026-09-30']);
    $magangRole = Role::create(['nama' => 'Magang', 'slug' => 'magang-laporan']);
    $magang = User::create(['nama' => 'Nadia Magang', 'email' => 'nadia@example.com', 'password' => 'password', 'role_id' => $magangRole->id, 'batch_id' => $batch->id, 'status' => 'aktif']);
    $pembimbingDua = User::create(['nama' => 'Pembimbing Dua', 'email' => 'pembimbing2@example.com', 'password' => 'password', 'role_id' => $user->role_id, 'status' => 'aktif']);
    $foto = PeranProduksi::create(['nama' => 'Fotografer', 'slug' => 'fotografer-laporan', 'aktif' => true]);
    $penulis = PeranProduksi::create(['nama' => 'Penulis Berita', 'slug' => 'penulis-laporan', 'aktif' => true]);
    $agenda = Agenda::create(['judul' => 'Sosialisasi KI', 'mulai_at' => '2026-07-12 09:00:00', 'status' => 'selesai', 'dibuat_oleh' => $user->id]);
    $paket = PaketKonten::create(['judul' => 'Berita Sosialisasi KI', 'status' => 'review', 'revisi_ke' => 0, 'dibuat_oleh' => $user->id]);
    $p1 = Penugasan::create(['user_id' => $magang->id, 'tipe' => 'berjam', 'mulai_at' => '2026-07-12 09:00:00', 'selesai_at' => '2026-07-12 12:00:00', 'untuk_type' => 'agenda', 'untuk_id' => $agenda->id, 'peran_id' => $foto->id, 'pembimbing_id' => $user->id, 'status' => 'selesai']);
    $p2 = Penugasan::create(['user_id' => $magang->id, 'tipe' => 'berdeadline', 'deadline_at' => '2026-07-15 17:00:00', 'untuk_type' => 'paket_konten', 'untuk_id' => $paket->id, 'peran_id' => $penulis->id, 'pembimbing_id' => $pembimbingDua->id, 'status' => 'selesai']);
    Draf::create(['paket_konten_id' => $paket->id, 'jenis' => 'berita', 'isi' => 'Karya latihan Nadia.', 'versi' => 1, 'asal' => 'manusia', 'latihan' => true, 'dibuat_oleh' => $magang->id]);
    CatatanPembimbing::create(['penugasan_id' => $p2->id, 'isi' => 'Perkuat lead berita.', 'oleh_id' => $pembimbingDua->id]);

    Livewire::actingAs($user)
        ->test(PusatLaporan::class)
        ->set('tab', 'magang')
        ->set('batchId', $batch->id)
        ->assertViewHas('rekapMagang', function ($rekap) {
            $nadia = $rekap->first();

            return $rekap->count() === 1
                && $nadia['nama'] === 'Nadia Magang'
                && $nadia['jumlah_penugasan'] === 2
                && $nadia['ragam_kegiatan'] === 2
                && $nadia['jumlah_pembimbing'] === 2
                && $nadia['karya_latihan'] === 1
                && $nadia['catatan_pembimbing'] === 1
                && $nadia['peran'] === ['Fotografer' => 1, 'Penulis Berita' => 1];
        })
        ->call('unduhCsvMagang')
        ->assertFileDownloaded('rekap-magang-magang-batch-7.csv');
});

it('mengunduh CSV publikasi sesuai filter aktif', function () {
    $user = penggunaLaporan();
    dataEvaluasiPrPlan($user);

    Livewire::actingAs($user)
        ->test(PusatLaporan::class)
        ->set('mulai', '2026-07-01')
        ->set('selesai', '2026-07-31')
        ->call('unduhCsvPublikasi')
        ->assertFileDownloaded('laporan-publikasi-2026-07-01-2026-07-31.csv');
});
