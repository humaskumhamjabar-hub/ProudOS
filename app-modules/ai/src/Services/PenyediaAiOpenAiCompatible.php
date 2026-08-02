<?php

namespace Modules\Ai\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\HasilAi;
use RuntimeException;

class PenyediaAiOpenAiCompatible implements PenyediaAi
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly KonfigurasiAiAktif $konfigurasi,
    ) {}

    public function tersedia(): bool
    {
        $konfigurasi = $this->konfigurasi->get();

        return filled($konfigurasi['api_key'])
            && filled($konfigurasi['base_url'])
            && filled($konfigurasi['model']);
    }

    public function hasilkan(string $jenis, string $judul, array $sumber): HasilAi
    {
        if (! $this->tersedia()) {
            throw new RuntimeException('Konfigurasi penyedia AI belum lengkap.');
        }

        $konfigurasi = $this->konfigurasi->get();
        $timeout = (int) $konfigurasi['timeout'];
        $this->sesuaikanBatasEksekusi($timeout);

        $request = $this->http
            ->withToken((string) $konfigurasi['api_key'])
            ->acceptJson()
            ->asJson()
            ->timeout($timeout);

        $baseUrl = rtrim($konfigurasi['base_url'], '/');

        if ($this->gunakanResponsesApi((string) $konfigurasi['model'])) {
            $response = $request->post($baseUrl.'/responses', [
                'model' => $konfigurasi['model'],
                'instructions' => $this->systemPrompt($jenis),
                'input' => $this->buatPrompt($jenis, $judul, $sumber),
            ]);
        } else {
            $response = $request->post($baseUrl.'/chat/completions', [
                'model' => $konfigurasi['model'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt($jenis),
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->buatPrompt($jenis, $judul, $sumber),
                    ],
                ],
                'temperature' => 0.2,
            ]);
        }

        if ($response->failed()) {
            throw new RuntimeException('Penyedia AI menolak permintaan dengan status '.$response->status().'.');
        }

        $isi = $this->gunakanResponsesApi((string) $konfigurasi['model'])
            ? $this->isiResponsesApi($response->json())
            : $response->json('choices.0.message.content');
        $model = $response->json('model', $konfigurasi['model']);

        if (! is_string($isi) || trim($isi) === '') {
            throw new RuntimeException('Penyedia AI mengembalikan respons yang tidak dapat dibaca.');
        }

        return new HasilAi(
            isi: trim($isi),
            model: is_string($model) ? $model : (string) $konfigurasi['model'],
            promptVersi: $konfigurasi['prompt_versi'],
        );
    }

    private function gunakanResponsesApi(string $model): bool
    {
        return preg_match('/(?:^|\/)gpt-5\.(?:5|6)(?:-|$)/', $model) === 1;
    }

    private function sesuaikanBatasEksekusi(int $timeout): void
    {
        $batasSaatIni = (int) ini_get('max_execution_time');
        $batasDiperlukan = $timeout + 5;

        if ($batasSaatIni > 0 && $batasSaatIni < $batasDiperlukan) {
            set_time_limit($batasDiperlukan);
        }
    }

    private function isiResponsesApi(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        if (is_string($payload['output_text'] ?? null)) {
            return $payload['output_text'];
        }

        foreach ($payload['output'] ?? [] as $output) {
            if (! is_array($output) || ($output['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($output['content'] ?? [] as $content) {
                if (is_array($content)
                    && ($content['type'] ?? null) === 'output_text'
                    && is_string($content['text'] ?? null)) {
                    return $content['text'];
                }
            }
        }

        return null;
    }

    /** @param array<int, string> $sumber */
    private function buatPrompt(string $jenis, string $judul, array $sumber): string
    {
        $label = match ($jenis) {
            'fakta' => 'Daftar fakta penting',
            'berita' => 'Draf berita',
            'berita_atensi' => 'Lima opsi judul dan satu naskah berita lengkap dari laporan atensi',
            'koreksi_berita_website' => 'Versi narasi website yang sudah diperbaiki sesuai instruksi koreksi',
            'konten_sosmed_pemerintah' => 'Paket konten media sosial lengkap sesuai struktur infografis, caption universal, dan thread X',
            'koreksi_konten_sosmed' => 'Versi lengkap konten media sosial yang sudah diperbaiki sesuai instruksi koreksi',
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

    private function systemPrompt(string $jenis): string
    {
        if ($jenis === 'konten_sosmed_pemerintah') {
            return $this->systemPromptKontenSosmed();
        }

        if ($jenis === 'koreksi_konten_sosmed') {
            return $this->systemPromptKontenSosmed()."\n\nPerbaiki KONTEN TERAKHIR hanya sesuai INSTRUKSI KOREKSI. Pertahankan seluruh bagian output yang tidak diminta berubah. Kembalikan paket konten lengkap, bukan daftar perubahan.";
        }

        if ($jenis === 'koreksi_berita_website') {
            return <<<'PROMPT'
            Anda adalah editor berita website tim Humas Kementerian Hukum Jawa Barat. Perbaiki narasi terakhir hanya sesuai instruksi koreksi pengguna. Pertahankan fakta, nama, jabatan, angka, tanggal, tempat, dan kutipan dari bahan asli. Jangan menambahkan fakta baru. Kembalikan naskah lengkap hasil revisi, bukan daftar perubahan dan bukan penjelasan proses.
            PROMPT;
        }

        if ($jenis !== 'berita_atensi') {
            return 'Anda membantu tim Humas Kementerian Hukum Jawa Barat. Tulis dalam bahasa Indonesia yang akurat, ringkas, netral, dan hanya memakai fakta dari sumber. Jangan mengarang nama, angka, kutipan, tanggal, atau jabatan.';
        }

        return <<<'PROMPT'
        Tugas utama Anda: Ubah Laporan Atensi Pimpinan menjadi satu naskah berita lengkap dengan 5 opsi judul yang relevan.

        Proses:
        1. Ekstraksi data: identifikasi nama kegiatan, waktu dan tempat, pihak yang hadir, serta uraian kegiatan dari laporan yang diberikan.
        2. Rangkai menjadi berita yang padat, informatif, akurat, netral, dan hanya memakai fakta dari sumber.
        3. Integrasikan Kepala Kantor Wilayah Kemenkum Jawa Barat, Asep Sutandar, ke dalam berita. Tautkan kehadirannya atau perwakilannya dengan arahan atau dukungan yang relevan. Jangan menyatakan Asep Sutandar hadir langsung jika sumber tidak menyebutkannya.
        4. Gunakan informasi pejabat Kanwil Kemenkum Jawa Barat yang tersedia di sumber. Jangan mengarang nama, jabatan, angka, tanggal, tempat, kutipan, atau fakta tambahan.
        5. Buat 5 opsi judul yang menarik dan kuat tanpa menyudutkan pihak mana pun. Setiap judul wajib memuat frasa "Kemenkum Jabar" dan harus bervariasi.

        Ketentuan:
        - Kata pertama naskah berita adalah kota tempat kegiatan dalam huruf kapital, diikuti " - ". Contoh: BANDUNG - Isi naskah berita.
        - P3H adalah Peraturan Perundang-undangan dan Pembinaan Hukum.
        - Jangan memakai subjudul Markdown seperti ## atau ### di tengah naskah. Tulis sebagai paragraf utuh.
        - Jangan gunakan nama "Kementerian Hukum dan HAM" atau "Kemenkumham". Gunakan "Kemenkum". "Kementerian HAM" tetap boleh bila kegiatan memang melibatkan Kementerian HAM.

        Format output:
        5 Opsi Judul:
        1. ...
        2. ...
        3. ...
        4. ...
        5. ...

        Naskah Berita:
        [KOTA] - [naskah berita dalam beberapa paragraf tanpa subjudul tambahan]
        PROMPT;
    }

    private function systemPromptKontenSosmed(): string
    {
        return <<<'PROMPT'
        INSTRUKSI UTAMA: AHLI KONTEN MEDIA SOSIAL PEMERINTAH

        Anda adalah ahli konten media sosial Kantor Wilayah Kementerian Hukum Jawa Barat. Ubah teks lengkap naskah berita menjadi konten siap pakai untuk Instagram, Facebook, TikTok, YouTube, dan X.

        ATURAN UNIVERSAL
        - Dilarang memakai nama "Kemenkumham" atau "Kementerian Hukum dan HAM". Selalu gunakan "Kemenkum" atau "Kementerian Hukum". "Kementerian HAM" tetap boleh bila sumber memang membahas lembaga tersebut.
        - Semua tanggal, nama, jabatan, lokasi, tujuan, dan fakta wajib akurat dari bahan.
        - P3H berarti Peraturan Perundang-undangan dan Pembinaan Hukum.
        - Pada Bagian 2 wajib disampaikan dampak kegiatan kepada masyarakat.

        BAGIAN 1: KONTEN VISUAL & TEKS PENDUKUNG

        1.A. KONTEN INFOGRAFIS (3 HALAMAN)
        Halaman 1: Judul Utama
        Tanggal Kegiatan: [tanggal acara dari berita]
        Judul Konten: [judul menarik, relevan, dan jelas mencerminkan judul asli kegiatan]
        Subjudul: [satu kalimat singkat tentang tujuan utama atau highlight kegiatan]

        Halaman 2: Rangkuman Awal
        [Maksimal 400 karakter dan tepat 2 paragraf]

        Halaman 3: Rangkuman Lanjutan
        [Maksimal 400 karakter dan tepat 2 paragraf]

        1.B. CAPTION UNIVERSAL & DESKRIPSI
        Caption ini dipakai sama untuk Instagram, Facebook, TikTok, dan deskripsi YouTube.
        - Paragraf pertama wajib dimulai dengan #WargiPengayoman.
        - Ringkas kegiatan secara informatif dan sebutkan nama kegiatan, tanggal, serta lokasi sesuai berita.
        - Sebelum blok hashtag, tambahkan satu pertanyaan CTA yang relevan untuk memancing komentar.
        - Akhiri seluruh caption tepat dengan blok berikut, tanpa hashtag atau teks tambahan setelahnya:

        #KementerianHukum
        #LayananHukumMakinMudah
        #KedahWBBM
        #kemenkumjabar

        BAGIAN 2: KONTEN X (TWITTER)

        - Gunakan bahasa informatif, jelas, dan naratif untuk menjelaskan alur kegiatan dari awal hingga akhir serta dampaknya kepada masyarakat.
        - Thread terdiri dari 20 sampai 50 post naratif, ditambah satu post pembuka dan satu post penutup.
        - Setiap post harus kurang dari 280 karakter.
        - Sajikan setiap post sebagai plaintext terpisah dan gunakan pemisah --- di antara post.
        - Post pembuka berisi judul dan rangkuman sangat pendek 1 sampai 2 kalimat, tanpa penomoran.
        - Post naratif memakai nomor 1/N, 2/N, dan seterusnya. N adalah jumlah post naratif, bukan menghitung pembuka dan penutup.
        - Setiap post naratif wajib diakhiri tepat dengan frasa: Kemenkum Jabar | Asep Sutandar.
        - Post penutup wajib memakai format tepat berikut. Tautan berita diisi kemudian setelah artikel website terbit:

        Berita selengkapnya pada tautan :

        [TAUTAN BERITA BELUM DIISI]

        Asep Sutandar
        Kemenkum Jabar

        Sosial Media Lainnya :
        Instagram: https://instagram.com/kemenkumjawabarat
        Youtube: https://youtube.com/@kemenkumjabar
        Facebook: https://facebook.com/kemenkumjabar
        X: https://twitter.com/Kemenkumjabar

        Kembalikan hasil final saja. Jangan gunakan blok kode Markdown dan jangan menjelaskan proses.
        PROMPT;
    }
}
