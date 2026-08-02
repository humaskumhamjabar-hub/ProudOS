<?php

use App\Livewire\KelolaTemplateVisual;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Visual\Models\Render;
use Modules\Visual\Models\TemplateAset;
use Modules\Visual\Models\TemplateLayout;
use Modules\Visual\Models\TemplateVisual;
use Modules\Work\Models\Tugas;
use Modules\Work\Models\TugasCatatanLapangan;

function penggunaTemplateVisual(bool $diizinkan = true): User
{
    $role = Role::create(['nama' => 'Pengelola Visual', 'slug' => 'pengelola-visual-'.($diizinkan ? 'ya' : 'tidak')]);

    if ($diizinkan) {
        $izin = Permission::create(['nama' => 'Kelola template visual', 'slug' => 'kelola_template_visual']);
        $role->permissions()->attach($izin);
    }

    return User::create([
        'nama' => 'Pengelola Visual',
        'email' => $diizinkan ? 'template@example.com' : 'tanpa-template@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

function templateVisualAktif(User $user): TemplateVisual
{
    $template = TemplateVisual::create([
        'nama' => 'Editorial Kanwil', 'format' => 'ig_carousel', 'rasio' => '4:5',
        'versi' => 1, 'status' => 'aktif', 'dibuat_oleh' => $user->id,
    ]);
    foreach (['cover', 'isi'] as $jenis) {
        TemplateLayout::create([
            'template_visual_id' => $template->id,
            'jenis' => $jenis,
            'definisi' => ['tema' => 'editorial'],
            'batas_karakter' => ['kicker' => 32, 'judul' => $jenis === 'cover' ? 90 : 72, 'isi' => $jenis === 'isi' ? 280 : 0],
        ]);
    }

    return $template;
}

function lengkapiBackgroundTemplate(TemplateVisual $template): void
{
    foreach ([1, 2, 3] as $urutan) {
        $path = "template-visual/{$template->id}/slide-{$urutan}.png";
        Storage::disk('local')->put($path, UploadedFile::fake()->image("slide-{$urutan}.png", 1080, 1350)->getContent());
        TemplateAset::create(['template_visual_id' => $template->id, 'jenis' => "background_slide_{$urutan}", 'path' => $path]);
    }
}

it('membatasi pengelolaan template lewat gate', function () {
    $this->actingAs(penggunaTemplateVisual(false))->get(route('visual.template'))->assertForbidden();
});

it('membuka versi tertinggi ketika beberapa versi diperbarui pada waktu yang sama', function () {
    $user = penggunaTemplateVisual();
    $waktuSama = now()->startOfSecond();

    foreach ([2, 3] as $versi) {
        TemplateVisual::create([
            'nama' => 'Video Vertikal Kanwil', 'format' => 'video_vertikal', 'rasio' => '9:16',
            'versi' => $versi, 'status' => $versi === 3 ? 'aktif' : 'arsip', 'dibuat_oleh' => $user->id,
            'created_at' => $waktuSama, 'updated_at' => $waktuSama,
        ]);
    }
    TemplateVisual::query()->update(['updated_at' => $waktuSama]);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->assertSet('templateId', TemplateVisual::where('versi', 3)->value('id'));
});

it('membatasi daftar versi agar tidak melebarkan layar ponsel', function () {
    $user = penggunaTemplateVisual();
    templateVisualAktif($user);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->assertSeeHtml('<section class="min-w-0" aria-labelledby="daftar-template">');
});

it('membuat versi draf tanpa mengubah versi aktif', function () {
    $user = penggunaTemplateVisual();
    $aktif = templateVisualAktif($user);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatVersiBaru', $aktif->id)
        ->assertSet('nama', 'Editorial Kanwil')
        ->assertSet('coverJudul', 90)
        ->set('coverJudul', 110)
        ->call('simpanDraf')
        ->assertHasNoErrors();

    $versi = TemplateVisual::where('versi', 2)->with('layouts')->sole();
    expect($aktif->fresh()->status)->toBe('aktif')
        ->and($aktif->layouts()->where('jenis', 'cover')->firstOrFail()->batas_karakter['judul'])->toBe(90)
        ->and($versi->status)->toBe('draf')
        ->and($versi->layouts->firstWhere('jenis', 'cover')->batas_karakter['judul'])->toBe(110);
});

it('mengarsipkan versi lama dari template yang sama saat versi baru diaktifkan', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();
    $lama = templateVisualAktif($user);
    lengkapiBackgroundTemplate($lama);
    $templateLain = $lama->replicate()->fill(['nama' => 'Template Alternatif', 'status' => 'aktif']);
    $templateLain->save();
    $baru = $lama->replicate()->fill(['versi' => 2, 'status' => 'draf']);
    $baru->save();
    foreach ($lama->layouts as $layout) {
        $baru->layouts()->create($layout->only(['jenis', 'definisi', 'batas_karakter']));
    }
    lengkapiBackgroundTemplate($baru);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('aktifkan', $baru->id)
        ->assertHasNoErrors();

    expect($lama->fresh()->status)->toBe('arsip')
        ->and($baru->fresh()->status)->toBe('aktif')
        ->and($templateLain->fresh()->status)->toBe('aktif')
        ->and(TemplateVisual::where('format', 'ig_carousel')->where('status', 'aktif')->count())->toBe(2);
});

it('admin mengunggah tepat tiga background png sebelum template tersedia', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();

    $halaman = Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Kegiatan Kanwil')
        ->set('backgroundSlides.0', UploadedFile::fake()->image('slide-1.png', 1080, 1350))
        ->set('backgroundSlides.1', UploadedFile::fake()->image('slide-2.png', 1080, 1350))
        ->set('backgroundSlides.2', UploadedFile::fake()->image('slide-3.png', 1080, 1350))
        ->call('simpanDraf')
        ->assertHasNoErrors();

    $template = TemplateVisual::with('aset')->sole();
    $halaman->call('aktifkan', $template->id)->assertHasNoErrors();

    expect($template->fresh()->status)->toBe('aktif')
        ->and($template->aset()->count())->toBe(3);
    foreach ($template->aset as $aset) {
        Storage::disk('local')->assertExists($aset->path);
        expect(getimagesize(Storage::disk('local')->path($aset->path)))->toMatchArray([1080, 1350]);
    }
});

it('menyimpan paket background lewat tombol khusus dan menampilkannya lagi setelah dimuat ulang', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Paket Visual Baru')
        ->set('backgroundSlides.0', UploadedFile::fake()->image('slide-1.png', 1080, 1350))
        ->set('backgroundSlides.1', UploadedFile::fake()->image('slide-2.png', 1080, 1350))
        ->set('backgroundSlides.2', UploadedFile::fake()->image('slide-3.png', 1080, 1350))
        ->call('simpanBackgroundCarousel')
        ->assertHasNoErrors()
        ->assertSee('3 background berhasil disimpan ke template.')
        ->assertSet('backgroundSlides', []);

    $template = TemplateVisual::with('aset')->sole();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->assertSet('templateId', $template->id)
        ->assertSee('3/3 tersimpan')
        ->assertSee('Tersimpan');

    expect($template->aset)->toHaveCount(3);
});

it('admin menyimpan penempatan foto dan area teks yang berbeda untuk setiap slide', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Template Penempatan')
        ->set('penempatanSlides.1.foto_slots.0.x', 120)
        ->set('penempatanSlides.1.foto_slots.0.y', 190)
        ->set('penempatanSlides.1.teks.x', 90)
        ->set('penempatanSlides.2.foto_slots.0.x', 68)
        ->set('penempatanSlides.2.teks.y', 860)
        ->call('simpanDraf')
        ->assertHasNoErrors();

    $template = TemplateVisual::with('layouts')->sole();
    $slideDua = $template->layouts->firstWhere('jenis', 'carousel_slide_2');
    $slideTiga = $template->layouts->firstWhere('jenis', 'carousel_slide_3');

    expect($slideDua->definisi['foto_slots'][0]['x'])->toBe(120)
        ->and($slideDua->definisi['teks']['x'])->toBe(90)
        ->and($slideTiga->definisi['foto_slots'][0]['x'])->toBe(68)
        ->and($slideTiga->definisi['teks']['y'])->toBe(860);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->assertSet('penempatanSlides.1.foto_slots.0.x', 120)
        ->assertSet('penempatanSlides.2.teks.y', 860)
        ->assertSee('Penempatan konten');
});

it('menolak penempatan template yang keluar dari kanvas', function () {
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Template Keluar Kanvas')
        ->set('penempatanSlides.1.foto_slots.0.x', 900)
        ->set('penempatanSlides.1.foto_slots.0.lebar', 500)
        ->call('simpanDraf')
        ->assertHasErrors(['penempatanSlides.1']);

    expect(TemplateVisual::count())->toBe(0);
});

it('mengganti satu background draf tanpa menghapus dua background lainnya', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();
    $template = templateVisualAktif($user);
    $template->update(['status' => 'draf']);
    lengkapiBackgroundTemplate($template);
    $pathLama = $template->aset()->where('jenis', 'background_slide_2')->value('path');

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->set('backgroundSlides.1', UploadedFile::fake()->image('pengganti.png', 1080, 1350))
        ->call('simpanBackgroundCarousel')
        ->assertHasNoErrors();

    $template->refresh();
    $pathBaru = $template->aset()->where('jenis', 'background_slide_2')->value('path');

    expect($template->aset()->count())->toBe(3)
        ->and($pathBaru)->not->toBe($pathLama);
    Storage::disk('local')->assertMissing($pathLama);
    Storage::disk('local')->assertExists($pathBaru);
});

it('versi baru menyalin seluruh background dari template aktif', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();
    $aktif = templateVisualAktif($user);
    lengkapiBackgroundTemplate($aktif);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatVersiBaru', $aktif->id)
        ->assertHasNoErrors();

    $versiBaru = TemplateVisual::where('versi', 2)->with('aset')->sole();

    expect($versiBaru->status)->toBe('draf')
        ->and($versiBaru->aset)->toHaveCount(3);
    foreach ($versiBaru->aset as $aset) {
        Storage::disk('local')->assertExists($aset->path);
    }
});

it('template aktif meminta versi baru sebelum background dapat diganti', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();
    $aktif = templateVisualAktif($user);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->set('backgroundSlides.0', UploadedFile::fake()->image('pengganti.png', 1080, 1350))
        ->call('simpanBackgroundCarousel')
        ->assertHasErrors(['template'])
        ->assertSee('Buat versi baru');

    expect($aktif->aset()->count())->toBe(0);
});

it('menolak dimensi background carousel yang tidak sesuai', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Dimensi Salah')
        ->set('backgroundSlides.0', UploadedFile::fake()->image('slide-1.png', 1200, 1200))
        ->set('backgroundSlides.1', UploadedFile::fake()->image('slide-2.png', 1080, 1350))
        ->set('backgroundSlides.2', UploadedFile::fake()->image('slide-3.png', 1080, 1350))
        ->call('simpanBackgroundCarousel')
        ->assertHasErrors(['backgroundSlides.0']);

    expect(TemplateVisual::count())->toBe(0)
        ->and(TemplateAset::count())->toBe(0);
});

it('menolak template carousel tersedia bila background belum lengkap', function () {
    $user = penggunaTemplateVisual();
    $template = templateVisualAktif($user);
    $template->update(['status' => 'draf']);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('aktifkan', $template->id)
        ->assertHasErrors(['template']);

    expect($template->fresh()->status)->toBe('draf');
});

it('menolak perubahan langsung pada template aktif', function () {
    $user = penggunaTemplateVisual();
    $aktif = templateVisualAktif($user);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->set('nama', 'Nama diubah')
        ->call('simpanDraf')
        ->assertHasErrors(['template']);

    expect($aktif->fresh()->nama)->toBe('Editorial Kanwil');
});

it('menyediakan penyusun template video tiga scene dengan rasio vertikal', function () {
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('format', 'video_vertikal')
        ->assertSet('rasio', '9:16')
        ->assertCount('videoScenes', 3)
        ->assertSee('Penyusun template video')
        ->assertSee('Kanvas kerja')
        ->assertSee('Preview animasi')
        ->assertSee('Putar scene aktif')
        ->assertSee('Preview semua scene')
        ->assertSee('Jeda')
        ->assertSee('Ulangi')
        ->assertSee('Pengaturan layer');
});

it('tetap merender preview ketika nilai posisi video sedang dikosongkan', function () {
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('format', 'video_vertikal')
        ->set('videoScenes.0.layers.0.x', '')
        ->set('videoScenes.0.layers.0.y', '')
        ->set('videoScenes.0.layers.0.lebar', '')
        ->set('videoScenes.0.layers.0.tinggi', '')
        ->assertSee('Kanvas kerja')
        ->assertSee('Atur Background');
});

it('menyimpan layout dan aset png setiap scene video', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Video Kegiatan Kanwil')
        ->set('format', 'video_vertikal')
        ->set('videoScenes.0.layers.1.x', -45)
        ->set('videoScenes.0.layers.1.animasi', 'masuk_kanan')
        ->set('videoScenes.1.layers.3.y', 1040)
        ->set('videoLayerUploads.0.0', UploadedFile::fake()->image('background.png', 1080, 1920))
        ->set('videoLayerUploads.0.1', UploadedFile::fake()->image('header.png', 1080, 150))
        ->set('videoLayerUploads.1.5', UploadedFile::fake()->image('footer.png', 1080, 160))
        ->call('simpanDraf')
        ->assertHasNoErrors()
        ->assertSet('videoLayerUploads', []);

    $template = TemplateVisual::with(['layouts', 'aset'])->sole();
    $scenePertama = $template->layouts->firstWhere('jenis', 'video_scene_1');
    $sceneKedua = $template->layouts->firstWhere('jenis', 'video_scene_2');

    expect($template->format)->toBe('video_vertikal')
        ->and($template->rasio)->toBe('9:16')
        ->and($template->layouts->whereIn('jenis', ['video_scene_1', 'video_scene_2', 'video_scene_3']))->toHaveCount(3)
        ->and($scenePertama->definisi['layers'][1]['x'])->toBe(-45)
        ->and($scenePertama->definisi['layers'][1]['animasi'])->toBe('masuk_kanan')
        ->and($sceneKedua->definisi['layers'][3]['y'])->toBe(1040)
        ->and($template->aset->pluck('jenis')->all())->toContain('video_scene_1_background', 'video_scene_1_header', 'video_scene_2_footer');

    foreach ($template->aset as $aset) {
        Storage::disk('local')->assertExists($aset->path);
    }

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->assertSet('format', 'video_vertikal')
        ->assertSet('videoScenes.0.layers.1.x', -45)
        ->assertSet('videoScenes.0.layers.1.animasi', 'masuk_kanan')
        ->assertSee('3/3 scene');
});

it('menolak layer video yang sepenuhnya di luar kanvas atau animasinya melewati scene', function () {
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Video Keluar Kanvas')
        ->set('format', 'video_vertikal')
        ->set('videoScenes.0.layers.2.x', -1080)
        ->set('videoScenes.0.layers.2.lebar', 400)
        ->call('simpanDraf')
        ->assertHasErrors(['videoScenes.0.layers.2']);

    expect(TemplateVisual::count())->toBe(0);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Animasi Terlambat')
        ->set('format', 'video_vertikal')
        ->set('videoScenes.0.durasi', 3)
        ->set('videoScenes.0.layers.1.mulai', 2.8)
        ->set('videoScenes.0.layers.1.durasi_animasi', 0.6)
        ->call('simpanDraf')
        ->assertHasErrors(['videoScenes.0.layers.1.mulai']);

    expect(TemplateVisual::count())->toBe(0);
});

it('menerima posisi negatif selama sebagian layer video masih terlihat', function () {
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Video Posisi Negatif')
        ->set('format', 'video_vertikal')
        ->set('videoScenes.0.layers.1.x', -120)
        ->set('videoScenes.0.layers.1.y', -40)
        ->set('videoScenes.0.layers.1.lebar', 1080)
        ->call('simpanDraf')
        ->assertHasNoErrors();

    $scene = TemplateVisual::with('layouts')->sole()->layouts->firstWhere('jenis', 'video_scene_1');

    expect($scene->definisi['layers'][1]['x'])->toBe(-120)
        ->and($scene->definisi['layers'][1]['y'])->toBe(-40);
});

it('menerima layer yang sebagian melewati sisi kanan dan bawah kanvas', function () {
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Video Melewati Tepi Kanvas')
        ->set('format', 'video_vertikal')
        ->set('videoScenes.0.layers.2.x', 900)
        ->set('videoScenes.0.layers.2.y', 1750)
        ->set('videoScenes.0.layers.2.lebar', 400)
        ->set('videoScenes.0.layers.2.tinggi', 400)
        ->call('simpanDraf')
        ->assertHasNoErrors();

    $scene = TemplateVisual::with('layouts')->sole()->layouts->firstWhere('jenis', 'video_scene_1');

    expect($scene->definisi['layers'][2]['x'])->toBe(900)
        ->and($scene->definisi['layers'][2]['y'])->toBe(1750);
});

it('mengaktifkan video setelah tiga scene tersimpan dan menyalin aset ke versi baru', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();

    $halaman = Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Video Tiga Scene')
        ->set('format', 'video_vertikal')
        ->set('videoLayerUploads.0.1', UploadedFile::fake()->image('header.png', 1080, 150))
        ->call('simpanDraf')
        ->assertHasNoErrors();

    $template = TemplateVisual::with(['layouts', 'aset'])->sole();
    $halaman->call('aktifkan', $template->id)->assertHasNoErrors();

    expect($template->fresh()->status)->toBe('aktif');

    $halaman->call('buatVersiBaru', $template->id)->assertHasNoErrors();
    $versiBaru = TemplateVisual::where('versi', 2)->with(['layouts', 'aset'])->sole();

    expect($versiBaru->status)->toBe('draf')
        ->and($versiBaru->layouts->whereIn('jenis', ['video_scene_1', 'video_scene_2', 'video_scene_3']))->toHaveCount(3)
        ->and($versiBaru->aset->pluck('jenis')->all())->toContain('video_scene_1_header');
    Storage::disk('local')->assertExists($versiBaru->aset->first()->path);
});

it('menyimpan seluruh perubahan video sebelum tersedia di editor', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('buatTemplateBaru')
        ->set('nama', 'Video Simpan Lengkap')
        ->set('format', 'video_vertikal')
        ->set('videoScenes.0.durasi', 9)
        ->set('videoScenes.0.layers.1.x', -80)
        ->set('videoScenes.1.layers.2.y', 320)
        ->set('videoScenes.2.layers.3.animasi', 'masuk_kanan')
        ->set('videoLayerUploads.0.1', UploadedFile::fake()->image('header-baru.png', 1080, 150))
        ->call('simpanDanAktifkan')
        ->assertHasNoErrors()
        ->assertSee('sudah aktif');

    $template = TemplateVisual::with(['layouts', 'aset'])->sole();
    $sceneSatu = $template->layouts->firstWhere('jenis', 'video_scene_1')->definisi;
    $sceneDua = $template->layouts->firstWhere('jenis', 'video_scene_2')->definisi;
    $sceneTiga = $template->layouts->firstWhere('jenis', 'video_scene_3')->definisi;

    expect($template->status)->toBe('aktif')
        ->and($sceneSatu['durasi'])->toBe(9)
        ->and($sceneSatu['layers'][1]['x'])->toBe(-80)
        ->and($sceneDua['layers'][2]['y'])->toBe(320)
        ->and($sceneTiga['layers'][3]['animasi'])->toBe('masuk_kanan')
        ->and($template->aset->pluck('jenis')->all())->toContain('video_scene_1_header');
    Storage::disk('local')->assertExists($template->aset->firstWhere('jenis', 'video_scene_1_header')->path);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->assertSet('templateId', $template->id)
        ->assertSet('videoScenes.0.durasi', 9)
        ->assertSet('videoScenes.0.layers.1.x', -80)
        ->assertSet('videoScenes.1.layers.2.y', 320)
        ->assertSet('videoScenes.2.layers.3.animasi', 'masuk_kanan');

    expect(TemplateVisual::findOrFail($template->id)->aset()->where('jenis', 'video_scene_1_header')->exists())->toBeTrue();
});

it('aset png video hanya dapat dilihat melalui template pemiliknya', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();
    $template = templateVisualAktif($user);
    $path = "template-visual/{$template->id}/video/header.png";
    Storage::disk('local')->put($path, UploadedFile::fake()->image('header.png', 1080, 150)->getContent());
    $aset = $template->aset()->create(['jenis' => 'video_scene_1_header', 'path' => $path]);
    $templateLain = TemplateVisual::create([
        'nama' => 'Template Lain', 'format' => 'video_vertikal', 'rasio' => '9:16',
        'versi' => 1, 'status' => 'draf', 'dibuat_oleh' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('visual.template.aset', [$template, $aset]))
        ->assertOk()
        ->assertHeader('content-type', 'image/png');

    $this->actingAs($user)
        ->get(route('visual.template.aset', [$templateLain, $aset]))
        ->assertNotFound();
});

it('admin menghapus template beserta layout aset dan referensi workspace', function () {
    Storage::fake('local');
    $user = penggunaTemplateVisual();
    $template = templateVisualAktif($user);
    lengkapiBackgroundTemplate($template);
    $paths = $template->aset()->pluck('path')->all();
    $tugas = Tugas::create(['judul' => 'Konten lama', 'status' => 'baru', 'dibuat_oleh' => $user->id]);
    $catatan = TugasCatatanLapangan::create([
        'tugas_id' => $tugas->id,
        'dibuat_oleh' => $user->id,
        'carousel_sosmed_template_id' => $template->id,
        'carousel_sosmed_template_versi' => $template->versi,
    ]);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('mintaHapusTemplate', $template->id)
        ->assertSet('templateHapusId', $template->id)
        ->assertSee('Ya, hapus versi ini')
        ->call('hapusTemplate', $template->id)
        ->assertHasNoErrors()
        ->assertSet('templateHapusId', null)
        ->assertSee('berhasil dihapus');

    expect(TemplateVisual::find($template->id))->toBeNull()
        ->and(TemplateLayout::where('template_visual_id', $template->id)->count())->toBe(0)
        ->and(TemplateAset::where('template_visual_id', $template->id)->count())->toBe(0)
        ->and($catatan->fresh()->carousel_sosmed_template_id)->toBeNull()
        ->and($catatan->fresh()->carousel_sosmed_template_versi)->toBeNull();
    foreach ($paths as $path) {
        Storage::disk('local')->assertMissing($path);
    }
});

it('template yang memiliki riwayat render tidak dapat dihapus', function () {
    $user = penggunaTemplateVisual();
    $template = templateVisualAktif($user);
    $paket = PaketKonten::create([
        'judul' => 'Riwayat render', 'status' => 'on_progress', 'revisi_ke' => 0, 'dibuat_oleh' => $user->id,
    ]);
    Render::create([
        'paket_konten_id' => $paket->id,
        'template_visual_id' => $template->id,
        'template_versi' => $template->versi,
        'format' => $template->format,
        'status' => 'selesai',
    ]);

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('mintaHapusTemplate', $template->id)
        ->call('hapusTemplate', $template->id)
        ->assertHasErrors(['template'])
        ->assertSee('riwayat render');

    expect(TemplateVisual::find($template->id))->not->toBeNull();
});
