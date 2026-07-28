<?php

use App\Jobs\RenderVideo;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Modules\Content\Models\Bahan;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\User;
use Modules\Visual\Actions\BangunVideoVertikal;
use Modules\Visual\Models\Render;
use Modules\Visual\Models\TemplateVisual;

it('passes local image paths to the video builder without loading image binaries', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $template = TemplateVisual::create(['nama' => 'Video Vertikal', 'format' => 'video_vertikal', 'rasio' => '9:16', 'versi' => 1, 'status' => 'aktif', 'durasi_per_slide_detik' => 4, 'dibuat_oleh' => $user->id]);
    $paket = PaketKonten::create(['judul' => 'Pelayanan Hukum', 'status' => 'on_progress', 'revisi_ke' => 0, 'dibuat_oleh' => $user->id]);
    $bahan = Bahan::create(['paket_konten_id' => $paket->id, 'tipe' => 'foto', 'path' => 'foto.jpg', 'nama_asli' => 'foto.jpg', 'mime' => 'image/jpeg', 'status_ekstraksi' => 'menunggu', 'dipakai_final' => true, 'diunggah_oleh' => $user->id, 'urutan' => 1]);
    Storage::disk('local')->put($bahan->path, 'image-binary-content');
    $render = Render::create(['paket_konten_id' => $paket->id, 'template_visual_id' => $template->id, 'template_versi' => $template->versi, 'format' => $template->format, 'status' => 'antre']);
    $render->slides()->create(['urutan' => 1, 'jenis' => 'cover', 'bahan_id' => $bahan->id, 'posisi_foto' => ['x' => 0, 'y' => 0, 'zoom' => 1], 'isi_teks' => ['judul' => $paket->judul]]);

    $pembangun = Mockery::mock(BangunVideoVertikal::class);
    $pembangun->shouldReceive('handle')->once()->withArgs(function (Render $renderDiterima, array $gambar) use ($render, $bahan) {
        return $renderDiterima->is($render)
            && $gambar === [$render->slides()->sole()->id => [
                'mime' => 'image/jpeg',
                'path' => Storage::disk('local')->path($bahan->path),
            ]];
    })->andReturn("render/{$render->id}/video-{$render->id}.mp4");

    (new RenderVideo($render->id))->handle($pembangun);

    $this->assertDatabaseHas('render', ['id' => $render->id, 'status' => 'selesai']);
});

it('membangun mp4 vertikal nyata dengan ffmpeg lokal', function () {
    $ffmpeg = '/opt/homebrew/bin/ffmpeg';

    if (! is_executable($ffmpeg)) {
        $this->markTestSkipped('FFmpeg lokal tidak tersedia.');
    }

    Storage::fake('local');
    config()->set('visual.ffmpeg_path', $ffmpeg);

    $user = User::factory()->create();
    $template = TemplateVisual::create([
        'nama' => 'Video Vertikal', 'format' => 'video_vertikal', 'rasio' => '9:16',
        'versi' => 1, 'status' => 'aktif', 'durasi_per_slide_detik' => 1, 'dibuat_oleh' => $user->id,
    ]);
    $paket = PaketKonten::create(['judul' => 'Pelayanan Hukum', 'status' => 'on_progress', 'revisi_ke' => 0, 'dibuat_oleh' => $user->id]);
    $bahan = Bahan::create([
        'paket_konten_id' => $paket->id, 'tipe' => 'foto', 'path' => 'foto.png',
        'nama_asli' => 'foto.png', 'mime' => 'image/png', 'status_ekstraksi' => 'menunggu',
        'dipakai_final' => true, 'diunggah_oleh' => $user->id, 'urutan' => 1,
    ]);
    $gambar = Process::run([
        $ffmpeg, '-y', '-f', 'lavfi', '-i', 'color=c=0x1e293b:s=108x192',
        '-frames:v', '1', Storage::disk('local')->path($bahan->path),
    ]);
    $gambar->throw();

    $render = Render::create([
        'paket_konten_id' => $paket->id, 'template_visual_id' => $template->id,
        'template_versi' => 1, 'format' => 'video_vertikal', 'status' => 'antre',
    ]);
    $render->slides()->create([
        'urutan' => 1, 'jenis' => 'cover', 'bahan_id' => $bahan->id,
        'posisi_foto' => ['x' => 0, 'y' => 0, 'zoom' => 1], 'isi_teks' => ['judul' => $paket->judul],
    ]);

    $path = app(BangunVideoVertikal::class)->handle($render, [
        $render->slides()->sole()->id => ['mime' => 'image/png', 'path' => Storage::disk('local')->path($bahan->path)],
    ]);

    expect(Storage::disk('local')->exists($path))->toBeTrue()
        ->and(Storage::disk('local')->size($path))->toBeGreaterThan(0);
});
