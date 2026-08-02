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

it('menaikkan batas eksekusi agar request ai dapat mengikuti timeout konfigurasi', function () {
    $batasAwal = (int) ini_get('max_execution_time');
    set_time_limit(30);
    config()->set('ai.timeout', 90);

    Http::fake([
        'https://ai.example.test/v1/chat/completions' => Http::response([
            'model' => 'proud-model-2026-07',
            'choices' => [['message' => ['content' => 'Konten selesai.']]],
        ]),
    ]);

    app(PenyediaAi::class)->hasilkan('konten_sosmed_pemerintah', 'Koordinasi Layanan', ['Naskah berita.']);

    expect((int) ini_get('max_execution_time'))->toBeGreaterThanOrEqual(95);

    set_time_limit($batasAwal);
});

it('menggunakan system prompt khusus laporan atensi untuk berita', function () {
    config()->set('ai.prompt_versi', 'berita-atensi-v1');

    Http::fake([
        'https://ai.example.test/v1/chat/completions' => Http::response([
            'model' => 'proud-model-2026-07',
            'choices' => [[
                'message' => ['content' => "1. Kemenkum Jabar Perkuat Layanan\n\nBANDUNG - Naskah berita."],
            ]],
        ]),
    ]);

    $hasil = app(PenyediaAi::class)->hasilkan('berita_atensi', 'Koordinasi Layanan', [
        'LAPORAN ATENSI: Kegiatan berlangsung di Bandung.',
    ]);

    expect($hasil->promptVersi)->toBe('berita-atensi-v1');

    Http::assertSent(function ($request) {
        $system = $request['messages'][0]['content'];

        return str_contains($system, 'Asep Sutandar')
            && str_contains($system, 'Setiap judul wajib memuat frasa "Kemenkum Jabar"')
            && str_contains($system, 'P3H adalah Peraturan Perundang-undangan dan Pembinaan Hukum')
            && str_contains($system, 'Jangan gunakan nama "Kementerian Hukum dan HAM"')
            && str_contains($request['messages'][1]['content'], 'Lima opsi judul dan satu naskah berita lengkap');
    });
});

it('menggunakan instruksi editor khusus saat mengoreksi narasi website', function () {
    Http::fake([
        'https://ai.example.test/v1/chat/completions' => Http::response([
            'model' => 'proud-model-2026-07',
            'choices' => [[
                'message' => ['content' => 'BANDUNG - Naskah yang sudah diperbaiki.'],
            ]],
        ]),
    ]);

    app(PenyediaAi::class)->hasilkan('koreksi_berita_website', 'Koordinasi Layanan', [
        'BAHAN ASLI: Kegiatan berlangsung di Bandung.',
        'NARASI TERAKHIR: BANDUNG - Naskah awal.',
        'INSTRUKSI KOREKSI: Pendekkan pembuka.',
    ]);

    Http::assertSent(function ($request) {
        return str_contains($request['messages'][0]['content'], 'Perbaiki narasi terakhir hanya sesuai instruksi koreksi')
            && str_contains($request['messages'][1]['content'], 'Pendekkan pembuka.');
    });
});

it('menggunakan system prompt lengkap untuk konten media sosial pemerintah', function () {
    Http::fake([
        'https://ai.example.test/v1/chat/completions' => Http::response([
            'model' => 'proud-model-2026-07',
            'choices' => [[
                'message' => ['content' => 'Paket konten media sosial lengkap.'],
            ]],
        ]),
    ]);

    app(PenyediaAi::class)->hasilkan('konten_sosmed_pemerintah', 'Koordinasi Layanan', [
        'TEKS LENGKAP NASKAH BERITA: Kegiatan berlangsung di Bandung.',
    ]);

    Http::assertSent(function ($request) {
        $system = $request['messages'][0]['content'];
        $user = $request['messages'][1]['content'];

        return str_contains($system, 'KONTEN INFOGRAFIS (3 HALAMAN)')
            && str_contains($system, '#WargiPengayoman')
            && str_contains($system, '20 sampai 50 post naratif')
            && str_contains($system, 'Kemenkum Jabar | Asep Sutandar')
            && str_contains($system, 'dampak kegiatan kepada masyarakat')
            && str_contains($system, 'Dilarang memakai nama "Kemenkumham"')
            && str_contains($system, 'P3H berarti Peraturan Perundang-undangan dan Pembinaan Hukum')
            && str_contains($system, 'https://instagram.com/kemenkumjawabarat')
            && str_contains($system, '[TAUTAN BERITA BELUM DIISI]')
            && str_contains($user, 'Kegiatan berlangsung di Bandung.')
            && ! str_contains($user, 'TAUTAN SUMBER BERITA');
    });
});

it('menggunakan responses api untuk model yang hanya mendukung endpoint responses', function () {
    config()->set('ai.model', 'codex/gpt-5.6-terra-high');

    Http::fake([
        'https://ai.example.test/v1/responses' => Http::response([
            'model' => 'gpt-5.6-terra-high',
            'output' => [[
                'type' => 'message',
                'content' => [[
                    'type' => 'output_text',
                    'text' => '  BANDUNG - Naskah dari Responses API.  ',
                ]],
            ]],
        ]),
    ]);

    $hasil = app(PenyediaAi::class)->hasilkan('berita_atensi', 'Koordinasi Layanan', [
        'LAPORAN ATENSI: Kegiatan berlangsung di Bandung.',
    ]);

    expect($hasil->isi)->toBe('BANDUNG - Naskah dari Responses API.')
        ->and($hasil->model)->toBe('gpt-5.6-terra-high');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://ai.example.test/v1/responses'
            && $request['model'] === 'codex/gpt-5.6-terra-high'
            && $request['instructions'] !== ''
            && str_contains($request['input'], 'Kegiatan berlangsung di Bandung.')
            && ! isset($request['temperature']);
    });
});

it('membaca output_text ringkas dari responses api', function () {
    config()->set('ai.model', 'gpt-5.6-terra');

    Http::fake([
        'https://ai.example.test/v1/responses' => Http::response([
            'model' => 'gpt-5.6-terra',
            'output_text' => 'Ringkasan dari Responses API.',
        ]),
    ]);

    $hasil = app(PenyediaAi::class)->hasilkan('ringkasan', 'Judul', ['Sumber']);

    expect($hasil->isi)->toBe('Ringkasan dari Responses API.');
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
