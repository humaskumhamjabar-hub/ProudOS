<?php

use App\Jobs\EkstrakTeksBahan;
use App\Livewire\KelolaProduksiKonten;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Content\Actions\EkstrakTeksDokumen;
use Modules\Content\Models\Bahan;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;

function penggunaEkstraksi(): User
{
    $role = Role::create(['nama' => 'Editor Ekstraksi', 'slug' => 'editor-ekstraksi']);
    $izin = Permission::create(['nama' => 'Kelola konten', 'slug' => 'kelola_konten']);
    $role->permissions()->attach($izin);

    return User::create([
        'nama' => 'Editor Ekstraksi',
        'email' => 'editor-ekstraksi@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

function bahanDokumen(User $user, string $path, string $namaAsli, string $mime): Bahan
{
    $paket = PaketKonten::create([
        'judul' => 'Sambutan Pimpinan',
        'status' => 'on_progress',
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ]);

    return Bahan::create([
        'paket_konten_id' => $paket->id,
        'tipe' => 'dokumen',
        'path' => $path,
        'nama_asli' => $namaAsli,
        'mime' => $mime,
        'status_ekstraksi' => 'menunggu',
        'dipakai_final' => false,
        'diunggah_oleh' => $user->id,
        'urutan' => 1,
    ]);
}

it('mengekstrak teks txt dan menormalkan spasi', function () {
    Storage::fake('local');
    $user = penggunaEkstraksi();
    $bahan = bahanDokumen($user, 'bahan/1/brief.txt', 'brief.txt', 'text/plain');
    Storage::disk('local')->put($bahan->path, "  Layanan AHU\r\n\r\n  mudah   dipahami masyarakat.  ");

    (new EkstrakTeksDokumen)->handle($bahan);

    expect($bahan->fresh()->status_ekstraksi)->toBe('selesai')
        ->and($bahan->fresh()->teks_terekstrak)->toBe("Layanan AHU\n\nmudah dipahami masyarakat.");
});

it('mengekstrak teks docx tanpa menjalankan binary eksternal', function () {
    Storage::fake('local');
    $user = penggunaEkstraksi();
    $bahan = bahanDokumen($user, 'bahan/1/sambutan.docx', 'sambutan.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $zipPath = tempnam(sys_get_temp_dir(), 'proud-docx-');
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Sambutan Kepala Kantor</w:t></w:r></w:p><w:p><w:r><w:t>Pelayanan hukum untuk masyarakat.</w:t></w:r></w:p></w:body></w:document>');
    $zip->close();
    Storage::disk('local')->put($bahan->path, file_get_contents($zipPath));
    unlink($zipPath);

    (new EkstrakTeksDokumen)->handle($bahan);

    expect($bahan->fresh()->status_ekstraksi)->toBe('selesai')
        ->and($bahan->fresh()->teks_terekstrak)->toContain('Sambutan Kepala Kantor')
        ->and($bahan->fresh()->teks_terekstrak)->toContain('Pelayanan hukum untuk masyarakat.');
});

it('menandai ekstraksi gagal tanpa menghapus bahan', function () {
    Storage::fake('local');
    $user = penggunaEkstraksi();
    $bahan = bahanDokumen($user, 'bahan/1/rusak.docx', 'rusak.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    Storage::disk('local')->put($bahan->path, 'bukan dokumen valid');

    expect(fn () => (new EkstrakTeksDokumen)->handle($bahan))->toThrow(RuntimeException::class);

    expect($bahan->fresh()->status_ekstraksi)->toBe('gagal')
        ->and($bahan->fresh()->teks_terekstrak)->toBeNull()
        ->and(Bahan::count())->toBe(1);
});

it('mengantrekan ekstraksi setelah upload dokumen dan dapat mencoba ulang', function () {
    Queue::fake();
    $user = penggunaEkstraksi();
    $bahan = bahanDokumen($user, 'bahan/1/sambutan.pdf', 'sambutan.pdf', 'application/pdf');
    $bahan->update(['status_ekstraksi' => 'gagal']);

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $bahan->paket_konten_id])
        ->call('cobaUlangEkstraksi', $bahan->id)
        ->assertHasNoErrors();

    expect($bahan->fresh()->status_ekstraksi)->toBe('menunggu');
    Queue::assertPushed(EkstrakTeksBahan::class, fn (EkstrakTeksBahan $job) => $job->bahanId === $bahan->id);
});

it('otomatis mengantrekan ekstraksi ketika dokumen diunggah', function () {
    Storage::fake('local');
    Queue::fake();
    $user = penggunaEkstraksi();
    $paket = PaketKonten::create([
        'judul' => 'Dokumen Sambutan',
        'status' => 'on_progress',
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(KelolaProduksiKonten::class, ['paket' => $paket->id])
        ->set('tipeBahan', 'dokumen')
        ->set('unggahanBahan', [UploadedFile::fake()->create('sambutan.txt', 10, 'text/plain')])
        ->call('unggahBahan')
        ->assertHasNoErrors();

    $bahan = Bahan::sole();
    Queue::assertPushed(EkstrakTeksBahan::class, fn (EkstrakTeksBahan $job) => $job->bahanId === $bahan->id);
});

it('job aman bila bahan sudah dihapus sebelum antrean berjalan', function () {
    $user = penggunaEkstraksi();
    $bahan = bahanDokumen($user, 'bahan/1/hilang.txt', 'hilang.txt', 'text/plain');
    $id = $bahan->id;
    $bahan->delete();

    (new EkstrakTeksBahan($id))->handle(new EkstrakTeksDokumen);

    expect(Bahan::count())->toBe(0);
});
