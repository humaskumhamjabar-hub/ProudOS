<?php

use Illuminate\Support\Facades\Http;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\Services\PenyediaAiNonaktif;
use Modules\Ai\Services\PenyediaAiOpenAiCompatible;

beforeEach(function () {
    config()->set('ai.provider', 'openai_compatible');
    config()->set('ai.base_url', 'https://ai.example.test/v1/');
    config()->set('ai.api_key', 'test-only-key');
    config()->set('ai.model', 'proud-test-model');
    config()->set('ai.timeout', 12);
    config()->set('ai.prompt_versi', 'konten-v2');
});

it('mengirim sumber ke endpoint kompatibel dan memetakan hasilnya', function () {
    Http::fake([
        'https://ai.example.test/v1/chat/completions' => Http::response([
            'model' => 'proud-model-2026-07',
            'choices' => [[
                'message' => ['content' => '  Pelayanan hukum menjangkau 120 pelaku UMKM.  '],
            ]],
        ]),
    ]);

    $penyedia = app(PenyediaAi::class);
    $hasil = $penyedia->hasilkan('ringkasan', 'Pelayanan Hukum Terpadu', [
        'Kegiatan diikuti 120 pelaku UMKM.',
        'Kegiatan berlangsung di Bandung.',
    ]);

    expect($penyedia)->toBeInstanceOf(PenyediaAiOpenAiCompatible::class)
        ->and($hasil->isi)->toBe('Pelayanan hukum menjangkau 120 pelaku UMKM.')
        ->and($hasil->model)->toBe('proud-model-2026-07')
        ->and($hasil->promptVersi)->toBe('konten-v2');

    Http::assertSent(function ($request) {
        $pesan = $request['messages'];

        return $request->url() === 'https://ai.example.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-only-key')
            && $request['model'] === 'proud-test-model'
            && $request['temperature'] === 0.2
            && str_contains($pesan[1]['content'], '120 pelaku UMKM')
            && str_contains($pesan[1]['content'], 'Bandung');
    });
});

it('tetap nonaktif tanpa konfigurasi lengkap dan tidak mengirim request', function () {
    config()->set('ai.api_key', null);
    Http::fake();

    $penyedia = app(PenyediaAi::class);

    expect($penyedia->tersedia())->toBeFalse();
    Http::assertNothingSent();
});

it('menggunakan provider nonaktif untuk nilai provider yang tidak dikenal', function () {
    config()->set('ai.provider', 'lainnya');

    expect(app(PenyediaAi::class))->toBeInstanceOf(PenyediaAiNonaktif::class);
});

it('menolak respons gagal atau kosong', function (array $response, int $status) {
    Http::fake([
        '*' => Http::response($response, $status),
    ]);

    expect(fn () => app(PenyediaAi::class)->hasilkan('fakta', 'Judul', ['Sumber']))
        ->toThrow(RuntimeException::class);
})->with([
    'provider gagal' => [['error' => ['message' => 'gagal']], 500],
    'hasil kosong' => [['choices' => []], 200],
]);
