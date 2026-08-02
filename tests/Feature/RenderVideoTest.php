<?php

use App\Actions\SimpanVideoSosmed;
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

it('membangun mp4 nyata memakai scene template video', function () {
    $ffmpeg = '/opt/homebrew/bin/ffmpeg';

    if (! is_executable($ffmpeg)) {
        $this->markTestSkipped('FFmpeg lokal tidak tersedia.');
    }

    Storage::fake('local');
    config()->set('visual.ffmpeg_path', $ffmpeg);
    $user = User::factory()->create();
    $template = TemplateVisual::create(['nama' => 'Template MP4', 'format' => 'video_vertikal', 'rasio' => '9:16', 'versi' => 1, 'status' => 'aktif', 'dibuat_oleh' => $user->id]);
    foreach ([1, 2, 3] as $urutan) {
        $template->layouts()->create([
            'jenis' => "video_scene_{$urutan}",
            'definisi' => ['durasi' => 3, 'layers' => [
                ['id' => 'foto', 'nama' => 'Foto', 'jenis' => 'foto', 'x' => 70, 'y' => 240, 'lebar' => 940, 'tinggi' => 690, 'urutan' => 10, 'animasi' => 'diam', 'mulai' => 0, 'durasi_animasi' => 0],
                ['id' => 'judul', 'nama' => 'Judul', 'jenis' => 'judul', 'x' => 70, 'y' => 1000, 'lebar' => 940, 'tinggi' => 300, 'urutan' => 20, 'animasi' => 'fade_in', 'mulai' => .5, 'durasi_animasi' => .6],
            ]],
            'batas_karakter' => [],
        ]);
    }
    $slides = collect([1, 2, 3])->map(function (int $urutan) use ($ffmpeg) {
        $path = "video-template-real/slide-{$urutan}.png";
        Storage::disk('local')->makeDirectory('video-template-real');
        Process::run([
            $ffmpeg, '-y', '-f', 'lavfi', '-i', 'color=c=0x1e293b:s=1080x1350',
            '-frames:v', '1', '-update', '1', Storage::disk('local')->path($path),
        ])->throw();

        return ['urutan' => $urutan, 'path' => $path, 'judul' => "Scene {$urutan}", 'isi' => 'Isi kegiatan'];
    })->all();

    $path = app(SimpanVideoSosmed::class)->handle([
        ['urutan' => 1, 'durasi' => 1, 'gerakan' => 'diam'],
        ['urutan' => 2, 'durasi' => 1, 'gerakan' => 'diam'],
        ['urutan' => 3, 'durasi' => 1, 'gerakan' => 'diam'],
    ], $slides, 991, $user->id, $template);

    expect(Storage::disk('local')->exists($path))->toBeTrue()
        ->and(Storage::disk('local')->size($path))->toBeGreaterThan(0)
        ->and(Storage::disk('local')->exists("tugas-sosmed/991/{$user->id}/video/template-scene-1.png"))->toBeTrue();
});

it('membangun video sosmed vertikal dari tiga hasil carousel', function () {
    $ffmpeg = '/opt/homebrew/bin/ffmpeg';

    if (! is_executable($ffmpeg)) {
        $this->markTestSkipped('FFmpeg lokal tidak tersedia.');
    }

    Storage::fake('local');
    config()->set('visual.ffmpeg_path', $ffmpeg);
    $user = User::factory()->create();
    $tugasId = 901;
    $slides = collect([1, 2, 3])->map(function (int $urutan) use ($ffmpeg) {
        $path = "video-sosmed/carousel-0{$urutan}.png";
        Storage::disk('local')->makeDirectory('video-sosmed');
        Process::run([
            $ffmpeg, '-y', '-f', 'lavfi', '-i', 'color=c=0x1e293b:s=1080x1350',
            '-frames:v', '1', '-update', '1', Storage::disk('local')->path($path),
        ])->throw();

        return ['urutan' => $urutan, 'path' => $path, 'mime' => 'image/png'];
    })->all();
    $scenes = [
        ['urutan' => 1, 'durasi' => 2, 'gerakan' => 'zoom_masuk'],
        ['urutan' => 2, 'durasi' => 2, 'gerakan' => 'geser_kiri'],
        ['urutan' => 3, 'durasi' => 2, 'gerakan' => 'zoom_keluar'],
    ];

    $path = app(SimpanVideoSosmed::class)->handle($scenes, $slides, $tugasId, $user->id);

    expect(Storage::disk('local')->exists($path))->toBeTrue()
        ->and(Storage::disk('local')->size($path))->toBeGreaterThan(0);

    $probe = Process::run([
        '/opt/homebrew/bin/ffprobe', '-v', 'error', '-select_streams', 'v:0',
        '-show_entries', 'stream=width,height', '-of', 'csv=p=0:s=x', Storage::disk('local')->path($path),
    ])->throw();
    expect(trim($probe->output()))->toBe('1080x1920');
});

it('membangun filter video dari layout aset dan animasi template', function () {
    Storage::fake('local');
    config()->set('visual.ffmpeg_path', '/bin/echo');
    $user = User::factory()->create();
    $template = TemplateVisual::create(['nama' => 'Layer Video', 'format' => 'video_vertikal', 'rasio' => '9:16', 'versi' => 1, 'status' => 'aktif', 'dibuat_oleh' => $user->id]);
    foreach ([1, 2, 3] as $urutan) {
        $template->layouts()->create([
            'jenis' => "video_scene_{$urutan}",
            'definisi' => ['durasi' => 6, 'layers' => [
                ['id' => 'header', 'nama' => 'Header', 'jenis' => 'png', 'x' => 0, 'y' => 0, 'lebar' => 1080, 'tinggi' => 180, 'urutan' => 10, 'animasi' => 'fade_in', 'mulai' => 0, 'durasi_animasi' => .6],
                ['id' => 'judul', 'nama' => 'Judul', 'jenis' => 'judul', 'x' => 70, 'y' => 1000, 'lebar' => 940, 'tinggi' => 250, 'urutan' => 20, 'animasi' => $urutan === 1 ? 'zoom_lembut' : 'naik', 'mulai' => 1, 'durasi_animasi' => .7],
            ]],
            'batas_karakter' => [],
        ]);
    }
    $assetPath = "template-visual/{$template->id}/video/header.png";
    Storage::disk('local')->put($assetPath, 'header-template');
    $template->aset()->create(['jenis' => 'video_scene_1_header', 'path' => $assetPath]);
    $slides = collect([1, 2, 3])->map(function (int $urutan) {
        $path = "video-template/slide-{$urutan}.png";
        Storage::disk('local')->put($path, "slide-{$urutan}");

        return ['urutan' => $urutan, 'path' => $path, 'judul' => "Judul {$urutan}", 'isi' => 'Isi kegiatan'];
    })->all();
    $perintahFfmpeg = [];
    Process::fake(function ($process) use (&$perintahFfmpeg) {
        $perintahFfmpeg[] = $process->command;
        $output = end($process->command);
        if (is_string($output) && str_ends_with($output, '.mp4')) {
            file_put_contents($output, 'video');
        } elseif (is_string($output) && str_ends_with($output, '.png')) {
            file_put_contents($output, 'poster');
        }

        return Process::result();
    });

    $path = app(SimpanVideoSosmed::class)->handle([
        ['urutan' => 1, 'durasi' => 6, 'gerakan' => 'diam'],
        ['urutan' => 2, 'durasi' => 6, 'gerakan' => 'diam'],
        ['urutan' => 3, 'durasi' => 6, 'gerakan' => 'diam'],
    ], $slides, 990, $user->id, $template);

    expect($path)->toEndWith('video-sosmed.mp4')
        ->and($perintahFfmpeg)->toHaveCount(7)
        ->and(collect($perintahFfmpeg[0])->contains(fn ($bagian) => is_string($bagian) && str_contains($bagian, 'fade=t=in:st=0:d=0.6:alpha=1')))->toBeTrue()
        ->and(collect($perintahFfmpeg[0])->contains(fn ($bagian) => is_string($bagian) && str_contains($bagian, "scale=w='iw*(0.94+0.06*")))->toBeTrue()
        ->and(collect($perintahFfmpeg[0])->contains(fn ($bagian) => is_string($bagian) && str_contains($bagian, "overlay=x='0':y='0'")))->toBeTrue()
        ->and(getimagesize(Storage::disk('local')->path("tugas-sosmed/990/{$user->id}/video/teks-scene-1-judul.png")))->toMatchArray([940, 250]);
});
