<?php

use App\Livewire\KelolaTemplateVisual;
use Livewire\Livewire;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Visual\Models\TemplateLayout;
use Modules\Visual\Models\TemplateVisual;

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

it('membatasi pengelolaan template lewat gate', function () {
    $this->actingAs(penggunaTemplateVisual(false))->get(route('visual.template'))->assertForbidden();
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

it('mengarsipkan template aktif berformat sama saat versi baru diaktifkan', function () {
    $user = penggunaTemplateVisual();
    $lama = templateVisualAktif($user);
    $baru = $lama->replicate()->fill(['versi' => 2, 'status' => 'draf']);
    $baru->save();
    foreach ($lama->layouts as $layout) {
        $baru->layouts()->create($layout->only(['jenis', 'definisi', 'batas_karakter']));
    }

    Livewire::actingAs($user)
        ->test(KelolaTemplateVisual::class)
        ->call('aktifkan', $baru->id)
        ->assertHasNoErrors();

    expect($lama->fresh()->status)->toBe('arsip')
        ->and($baru->fresh()->status)->toBe('aktif')
        ->and(TemplateVisual::where('format', 'ig_carousel')->where('status', 'aktif')->count())->toBe(1);
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
