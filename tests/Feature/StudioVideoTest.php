<?php

use App\Jobs\RenderVideo;
use App\Livewire\StudioVideo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Content\Models\Bahan;
use Modules\Content\Models\PaketKonten;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Modules\Visual\Models\Render;
use Modules\Visual\Models\TemplateVisual;

function penggunaStudioVideo(bool $izin = true): User
{
    $role = Role::create(['nama' => 'Video', 'slug' => 'video-'.($izin ? 'ya' : 'tidak')]);
    if ($izin) {
        $permission = Permission::create(['nama' => 'Kelola konten', 'slug' => 'kelola_konten']);
        $role->permissions()->attach($permission);
    }

    return User::create(['nama' => 'Editor Video', 'email' => $izin ? 'video@example.com' : 'tanpa-video@example.com', 'password' => 'password', 'role_id' => $role->id, 'status' => 'aktif']);
}

it('membatasi studio video lewat gate', function () {
    $this->actingAs(penggunaStudioVideo(false))->get(route('visual.video'))->assertForbidden();
});

it('menolak render video untuk paket yang sudah diarsipkan meski paketId diubah', function () {
    Queue::fake();
    $user = penggunaStudioVideo();
    $template = TemplateVisual::create(['nama' => 'Video Vertikal', 'format' => 'video_vertikal', 'rasio' => '9:16', 'versi' => 1, 'status' => 'aktif', 'durasi_per_slide_detik' => 4, 'dibuat_oleh' => $user->id]);
    $paketAktif = PaketKonten::create(['judul' => 'Masih Aktif', 'status' => 'on_progress', 'revisi_ke' => 0, 'dibuat_oleh' => $user->id]);
    $paketArsip = PaketKonten::create(['judul' => 'Sudah Arsip', 'status' => 'arsip', 'revisi_ke' => 0, 'dibuat_oleh' => $user->id]);
    Bahan::create(['paket_konten_id' => $paketArsip->id, 'tipe' => 'foto', 'path' => 'foto-arsip.jpg', 'nama_asli' => 'foto-arsip.jpg', 'mime' => 'image/jpeg', 'status_ekstraksi' => 'menunggu', 'dipakai_final' => true, 'diunggah_oleh' => $user->id, 'urutan' => 1]);

    expect(fn () => Livewire::actingAs($user)->test(StudioVideo::class, ['paket' => $paketAktif->id])
        ->set('templateId', $template->id)
        ->set('paketId', $paketArsip->id)
        ->call('buatVideo'))
        ->toThrow(ModelNotFoundException::class);

    expect(Render::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('sets queue retry windows longer than the video job timeout', function () {
    $timeout = (new RenderVideo(1))->timeout;

    expect(config('queue.connections.database.retry_after'))->toBeGreaterThan($timeout)
        ->and(config('queue.connections.redis.retry_after'))->toBeGreaterThan($timeout);
});

it('membuat render video dari foto final dan memasukkannya ke antrean', function () {
    Queue::fake();
    $user = penggunaStudioVideo();
    $template = TemplateVisual::create(['nama' => 'Video Vertikal', 'format' => 'video_vertikal', 'rasio' => '9:16', 'versi' => 1, 'status' => 'aktif', 'durasi_per_slide_detik' => 4, 'dibuat_oleh' => $user->id]);
    $paket = PaketKonten::create(['judul' => 'Pelayanan Hukum', 'status' => 'on_progress', 'revisi_ke' => 0, 'dibuat_oleh' => $user->id]);
    foreach ([1, 2] as $urutan) {
        Bahan::create(['paket_konten_id' => $paket->id, 'tipe' => 'foto', 'path' => "foto-{$urutan}.jpg", 'nama_asli' => "foto-{$urutan}.jpg", 'mime' => 'image/jpeg', 'status_ekstraksi' => 'menunggu', 'dipakai_final' => true, 'diunggah_oleh' => $user->id, 'urutan' => $urutan]);
    }

    Livewire::actingAs($user)->test(StudioVideo::class, ['paket' => $paket->id])
        ->set('templateId', $template->id)->call('buatVideo')->assertHasNoErrors();

    $render = Render::with('slides')->sole();
    expect($render->format)->toBe('video_vertikal')->and($render->slides)->toHaveCount(2);
    Queue::assertPushed(RenderVideo::class, fn ($job) => $job->renderId === $render->id);
});
