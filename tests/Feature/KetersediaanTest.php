<?php

use Illuminate\Validation\ValidationException;
use Modules\People\Models\Ketidakhadiran;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Scheduling\Actions\BuatPenugasan;
use Modules\Scheduling\Models\Penugasan;
use Modules\Scheduling\Models\PeranProduksi;

beforeEach(function () {
    $this->role = Role::create(['nama' => 'Staf', 'slug' => 'staf']);
    $this->peran = PeranProduksi::create(['nama' => 'Peliput', 'slug' => 'peliput', 'aktif' => true]);
    $this->orang = User::create([
        'nama' => 'Tester',
        'email' => 'tester@example.com',
        'password' => 'password',
        'role_id' => $this->role->id,
        'status' => 'aktif',
    ]);
});

function dataPenugasanBerjam(array $override = []): array
{
    return array_merge([
        'tipe' => 'berjam',
        'mulai_at' => now()->addDay()->setTime(9, 0)->toDateTimeString(),
        'selesai_at' => now()->addDay()->setTime(12, 0)->toDateTimeString(),
        'untuk_type' => 'agenda',
        'untuk_id' => 1,
        'status' => 'aktif',
    ], $override);
}

it('menolak penugasan saat ada ketidakhadiran aktif (TEMBOK)', function () {
    Ketidakhadiran::create([
        'user_id' => $this->orang->id,
        'jenis' => 'cuti',
        'mulai' => now()->addDay()->toDateString(),
        'selesai' => now()->addDays(2)->toDateString(),
        'dicatat_oleh' => $this->orang->id,
    ]);

    app(BuatPenugasan::class)->handle(dataPenugasanBerjam([
        'user_id' => $this->orang->id,
        'peran_id' => $this->peran->id,
    ]));
})->throws(ValidationException::class);

it('menolak penugasan di luar masa akses (magang kedaluwarsa)', function () {
    $this->orang->update(['aktif_sampai' => now()->subDay()->toDateString()]);

    app(BuatPenugasan::class)->handle(dataPenugasanBerjam([
        'user_id' => $this->orang->id,
        'peran_id' => $this->peran->id,
    ]));
})->throws(ValidationException::class);

it('menahan bentrok jam, tapi bisa diterobos — penugasan lama jadi butuh_pengganti', function () {
    $pertama = app(BuatPenugasan::class)->handle(dataPenugasanBerjam([
        'user_id' => $this->orang->id,
        'peran_id' => $this->peran->id,
    ]));

    // Tanpa terobos: ditolak.
    expect(fn () => app(BuatPenugasan::class)->handle(dataPenugasanBerjam([
        'user_id' => $this->orang->id,
        'peran_id' => $this->peran->id,
        'mulai_at' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
        'selesai_at' => now()->addDay()->setTime(13, 0)->toDateTimeString(),
    ])))->toThrow(ValidationException::class);

    // Dengan terobos: berhasil, dan yang lama tidak dihapus — butuh_pengganti.
    $kedua = app(BuatPenugasan::class)->handle(dataPenugasanBerjam([
        'user_id' => $this->orang->id,
        'peran_id' => $this->peran->id,
        'mulai_at' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
        'selesai_at' => now()->addDay()->setTime(13, 0)->toDateTimeString(),
    ]), terobos: true);

    expect($pertama->fresh()->status)->toBe('butuh_pengganti')
        ->and($kedua->digantikan_dari_id)->toBe($pertama->id);
});

it('penugasan berdeadline tidak pernah bentrok dengan apa pun', function () {
    app(BuatPenugasan::class)->handle(dataPenugasanBerjam([
        'user_id' => $this->orang->id,
        'peran_id' => $this->peran->id,
    ]));

    $deadline = app(BuatPenugasan::class)->handle([
        'user_id' => $this->orang->id,
        'peran_id' => $this->peran->id,
        'tipe' => 'berdeadline',
        'deadline_at' => now()->addDay()->setTime(11, 0)->toDateTimeString(),
        'untuk_type' => 'tugas',
        'untuk_id' => 1,
        'status' => 'aktif',
    ]);

    expect($deadline->exists)->toBeTrue()
        ->and(Penugasan::count())->toBe(2);
});

it('jam yang tidak tumpang tindih di hari yang sama tidak dianggap bentrok', function () {
    app(BuatPenugasan::class)->handle(dataPenugasanBerjam([
        'user_id' => $this->orang->id,
        'peran_id' => $this->peran->id,
    ]));

    // 13:00–15:00 di hari yang sama: liputan pagi mengunci jamnya saja, bukan harinya.
    $sore = app(BuatPenugasan::class)->handle(dataPenugasanBerjam([
        'user_id' => $this->orang->id,
        'peran_id' => $this->peran->id,
        'mulai_at' => now()->addDay()->setTime(13, 0)->toDateTimeString(),
        'selesai_at' => now()->addDay()->setTime(15, 0)->toDateTimeString(),
    ]));

    expect($sore->exists)->toBeTrue();
});
