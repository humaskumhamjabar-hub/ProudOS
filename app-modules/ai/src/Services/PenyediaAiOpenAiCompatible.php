<?php

namespace Modules\Ai\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\HasilAi;
use RuntimeException;

class PenyediaAiOpenAiCompatible implements PenyediaAi
{
    public function __construct(private readonly HttpFactory $http) {}

    public function tersedia(): bool
    {
        return filled(config('ai.api_key'))
            && filled(config('ai.base_url'))
            && filled(config('ai.model'));
    }

    public function hasilkan(string $jenis, string $judul, array $sumber): HasilAi
    {
        if (! $this->tersedia()) {
            throw new RuntimeException('Konfigurasi penyedia AI belum lengkap.');
        }

        $response = $this->http
            ->withToken((string) config('ai.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('ai.timeout', 30))
            ->post(rtrim((string) config('ai.base_url'), '/').'/chat/completions', [
                'model' => config('ai.model'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda membantu tim Humas Kementerian Hukum Jawa Barat. Tulis dalam bahasa Indonesia yang akurat, ringkas, netral, dan hanya memakai fakta dari sumber. Jangan mengarang nama, angka, kutipan, tanggal, atau jabatan.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buatPrompt($jenis, $judul, $sumber),
                    ],
                ],
                'temperature' => 0.2,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Penyedia AI menolak permintaan dengan status '.$response->status().'.');
        }

        $isi = $response->json('choices.0.message.content');
        $model = $response->json('model', config('ai.model'));

        if (! is_string($isi) || trim($isi) === '') {
            throw new RuntimeException('Penyedia AI mengembalikan respons yang tidak dapat dibaca.');
        }

        return new HasilAi(
            isi: trim($isi),
            model: is_string($model) ? $model : (string) config('ai.model'),
            promptVersi: (string) config('ai.prompt_versi', 'konten-v1'),
        );
    }

    /** @param array<int, string> $sumber */
    private function buatPrompt(string $jenis, string $judul, array $sumber): string
    {
        $label = match ($jenis) {
            'fakta' => 'Daftar fakta penting',
            'berita' => 'Draf berita',
            'caption' => 'Caption media sosial',
            'opsi_judul' => 'Beberapa opsi judul',
            'ringkasan' => 'Ringkasan singkat',
            default => 'Usulan konten',
        };

        $bahan = collect($sumber)
            ->map(fn (string $teks, int $index) => ($index + 1).'. '.trim($teks))
            ->implode("\n\n");

        return <<<PROMPT
        Buat {$label} untuk paket konten berjudul "{$judul}".

        Gunakan hanya bahan berikut:
        {$bahan}

        Kembalikan teks final saja, tanpa penjelasan proses dan tanpa blok kode.
        PROMPT;
    }
}
