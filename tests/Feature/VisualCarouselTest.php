<?php

use App\Jobs\RenderCarousel;
use App\Livewire\StudioCarousel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Content\Models\Bahan;
use Modules\Content\Models\Draf;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Visual\Actions\BangunCarouselPng;
use Modules\Visual\Contracts\PenangkapHtml;
use Modules\Visual\Models\Render;
use Modules\Visual\Models\TemplateLayout;
use Modules\Visual\Models\TemplateVisual;

function penggunaStudioCarousel(bool $denganIzin = true): User
{
    $role = Role::create(['nama' => 'Desainer Konten', 'slug' => 'desainer-konten']);

    if ($denganIzin) {
        $izin = Permission::create(['nama' => 'Kelola konten', 'slug' => 'kelola_konten']);
        $role->permissions()->attach($izin);
    }

    return User::create([
        'nama' => 'Desainer Konten',
        'email' => $denganIzin ? 'desainer@example.com' : 'tanpa-izin-visual@example.com',
        'password' => 'password',
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);
}

function templateCarousel(User $user): TemplateVisual
{
    $template = TemplateVisual::create([
        'nama' => 'Editorial Kanwil',
        'format' => 'ig_carousel',
        'rasio' => '4:5',
        'versi' => 1,
        'status' => 'aktif',
        'dibuat_oleh' => $user->id,
    ]);

    TemplateLayout::create([
        'template_visual_id' => $template->id,
        'jenis' => 'cover',
        'definisi' => ['tema' => 'editorial-kanwil'],
        'batas_karakter' => ['judul' => 90, 'kicker' => 32],
    ]);
    TemplateLayout::create([
        'template_visual_id' => $template->id,
        'jenis' => 'isi',
        'definisi' => ['tema' => 'editorial-kanwil'],
        'batas_karakter' => ['judul' => 72, 'isi' => 280],
    ]);

    return $template;
}

function paketCarousel(User $user, int $jumlahFoto = 3): PaketKonten
{
    $paket = PaketKonten::create([
        'judul' => 'Layanan Hukum Hadir Lebih Dekat untuk UMKM',
        'subjudul' => 'Kolaborasi Kanwil Kemenkum Jawa Barat',
        'status' => 'on_progress',
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ]);

    Draf::create([
        'paket_konten_id' => $paket->id,
        'jenis' => 'caption',
        'isi' => 'Sebanyak 120 pelaku UMKM mendapatkan penguatan pelindungan merek.',
        'versi' => 1,
        'asal' => 'manusia',
        'latihan' => false,
        'dibuat_oleh' => $user->id,
    ]);

    foreach (range(1, $jumlahFoto) as $urutan) {
        Bahan::create([
            'paket_konten_id' => $paket->id,
            'tipe' => 'foto',
            'path' => "bahan/{$paket->id}/foto-{$urutan}.jpg",
            'nama_asli' => "foto-{$urutan}.jpg",
            'mime' => 'image/jpeg',
            'status_ekstraksi' => 'menunggu',
            'dipakai_final' => true,
            'diunggah_oleh' => $user->id,
            'urutan' => $urutan,
        ]);
    }

    return $paket;
}

it('membatasi studio carousel lewat izin kelola konten', function () {
    $this->actingAs(penggunaStudioCarousel(false))
        ->get(route('visual.carousel'))
        ->assertForbidden();
});

it('membuat satu cover dan slide isi dari foto final paket', function () {
    Queue::fake();
    $user = penggunaStudioCarousel();
    $template = templateCarousel($user);
    $paket = paketCarousel($user, 3);

    Livewire::actingAs($user)
        ->test(StudioCarousel::class, ['paket' => $paket->id])
        ->set('templateId', $template->id)
        ->call('siapkanCarousel')
        ->assertHasNoErrors();

    $render = Render::with('slides')->sole();

    expect($render->paket_konten_id)->toBe($paket->id)
        ->and($render->template_visual_id)->toBe($template->id)
        ->and($render->template_versi)->toBe(1)
        ->and($render->format)->toBe('ig_carousel')
        ->and($render->status)->toBe('antre')
        ->and($render->slides)->toHaveCount(3)
        ->and($render->slides->pluck('jenis')->all())->toBe(['cover', 'isi', 'isi'])
        ->and($render->slides->first()->isi_teks['judul'])->toBe($paket->judul);

    Queue::assertPushed(RenderCarousel::class, fn (RenderCarousel $job) => $job->renderId === $render->id);
});

it('mensyaratkan foto final dan template aktif', function () {
    Queue::fake();
    $user = penggunaStudioCarousel();
    $template = templateCarousel($user);
    $template->update(['status' => 'draf']);
    $paket = paketCarousel($user, 1);
    $paket->bahan()->update(['dipakai_final' => false]);

    Livewire::actingAs($user)
        ->test(StudioCarousel::class, ['paket' => $paket->id])
        ->set('templateId', $template->id)
        ->call('siapkanCarousel')
        ->assertHasErrors(['carousel']);

    expect(Render::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('menyimpan posisi crop dan teks slide dalam batas template', function () {
    Queue::fake();
    $user = penggunaStudioCarousel();
    $template = templateCarousel($user);
    $paket = paketCarousel($user, 2);
    $komponen = Livewire::actingAs($user)
        ->test(StudioCarousel::class, ['paket' => $paket->id])
        ->set('templateId', $template->id)
        ->call('siapkanCarousel');
    $slide = Render::sole()->slides()->where('jenis', 'isi')->firstOrFail();

    $komponen
        ->call('pilihSlide', $slide->id)
        ->set('posisiX', 24)
        ->set('posisiY', -18)
        ->set('zoom', 1.45)
        ->set('judulSlide', 'Pelindungan merek membuka ruang tumbuh')
        ->set('isiSlide', 'Pelaku UMKM menerima pendampingan langsung mengenai pendaftaran dan pelindungan merek.')
        ->call('simpanSlide')
        ->assertHasNoErrors();

    expect($slide->fresh()->posisi_foto)->toBe(['x' => 24, 'y' => -18, 'zoom' => 1.45])
        ->and($slide->fresh()->isi_teks['judul'])->toBe('Pelindungan merek membuka ruang tumbuh');

    $komponen
        ->set('judulSlide', str_repeat('x', 73))
        ->call('simpanSlide')
        ->assertHasErrors(['judulSlide']);
});

it('tidak mengizinkan render milik paket lain diubah', function () {
    Queue::fake();
    $user = penggunaStudioCarousel();
    $template = templateCarousel($user);
    $paket = paketCarousel($user, 1);
    $paketLain = PaketKonten::create([
        'judul' => 'Paket lain',
        'status' => 'on_progress',
        'revisi_ke' => 0,
        'dibuat_oleh' => $user->id,
    ]);
    $renderLain = Render::create([
        'paket_konten_id' => $paketLain->id,
        'template_visual_id' => $template->id,
        'template_versi' => 1,
        'format' => 'ig_carousel',
        'status' => 'antre',
    ]);

    expect(fn () => Livewire::actingAs($user)
        ->test(StudioCarousel::class, ['paket' => $paket->id])
        ->call('pilihRender', $renderLain->id))
        ->toThrow(ModelNotFoundException::class);
});

it('menghasilkan PNG 1080x1350 dan membungkus semua slide ke ZIP', function () {
    Storage::fake('local');
    app()->instance(PenangkapHtml::class, new class implements PenangkapHtml
    {
        public function tangkap(string $htmlPath, string $pngPath, int $lebar, int $tinggi): void
        {
            $gambar = imagecreatetruecolor($lebar, $tinggi);
            imagepng($gambar, $pngPath);
            imagedestroy($gambar);
        }
    });
    $user = penggunaStudioCarousel();
    $template = templateCarousel($user);
    $paket = paketCarousel($user, 2);
    $render = Render::create([
        'paket_konten_id' => $paket->id,
        'template_visual_id' => $template->id,
        'template_versi' => 1,
        'format' => 'ig_carousel',
        'status' => 'proses',
    ]);
    $gambarSatuPiksel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl7wAAAAABJRU5ErkJggg==');
    $gambar = [];

    foreach ($paket->bahan()->orderBy('urutan')->get() as $index => $bahan) {
        $slide = $render->slides()->create([
            'urutan' => $index + 1,
            'jenis' => $index === 0 ? 'cover' : 'isi',
            'bahan_id' => $bahan->id,
            'posisi_foto' => ['x' => 0, 'y' => 0, 'zoom' => 1],
            'isi_teks' => ['kicker' => 'KEMENKUM JABAR', 'judul' => "Slide {$index}", 'isi' => 'Isi pendukung.'],
        ]);
        $gambar[$slide->id] = ['mime' => 'image/png', 'data' => $gambarSatuPiksel];
    }

    $path = app(BangunCarouselPng::class)->handle($render, $gambar);

    Storage::disk('local')->assertExists($path);
    foreach ([1, 2] as $urutan) {
        $png = Storage::disk('local')->path("render/{$render->id}/slide-{$urutan}.png");
        expect(getimagesize($png))->toMatchArray([1080, 1350]);
    }

    $zip = new ZipArchive;
    expect($zip->open(Storage::disk('local')->path($path)))->toBeTrue()
        ->and($zip->numFiles)->toBe(2)
        ->and($zip->getNameIndex(0))->toBe('slide-01.png')
        ->and($zip->getNameIndex(1))->toBe('slide-02.png');
    $zip->close();
});
