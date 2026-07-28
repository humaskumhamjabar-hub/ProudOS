<?php

namespace Modules\Content\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Content\Models\Bahan;
use RuntimeException;
use Smalot\PdfParser\Parser;
use ZipArchive;

class EkstrakTeksDokumen
{
    public function handle(Bahan $bahan): void
    {
        $bahan->update(['status_ekstraksi' => 'menunggu']);

        try {
            $teks = match (strtolower(pathinfo($bahan->nama_asli, PATHINFO_EXTENSION))) {
                'txt' => Storage::disk('local')->get($bahan->path),
                'docx' => $this->dariDocx(Storage::disk('local')->path($bahan->path)),
                'pdf' => $this->dariPdf(Storage::disk('local')->path($bahan->path)),
                default => throw new RuntimeException('Format dokumen belum didukung untuk ekstraksi teks.'),
            };

            $teks = $this->normalisasi($teks);
            throw_if($teks === '', RuntimeException::class, 'Dokumen tidak menghasilkan teks yang dapat dibaca.');

            $bahan->update([
                'teks_terekstrak' => $teks,
                'status_ekstraksi' => 'selesai',
            ]);
        } catch (\Throwable $exception) {
            $bahan->update([
                'teks_terekstrak' => null,
                'status_ekstraksi' => 'gagal',
            ]);

            throw new RuntimeException('Ekstraksi teks gagal: '.$exception->getMessage(), previous: $exception);
        }
    }

    private function dariDocx(string $path): string
    {
        $zip = new ZipArchive;
        throw_unless($zip->open($path) === true, RuntimeException::class, 'File DOCX tidak dapat dibuka.');

        try {
            $xml = $zip->getFromName('word/document.xml');
            throw_if($xml === false, RuntimeException::class, 'Isi dokumen DOCX tidak ditemukan.');
        } finally {
            $zip->close();
        }

        $xml = preg_replace('/<w:(tab|br|cr)\b[^>]*\/?\s*>/u', "\n", $xml);
        $xml = preg_replace('/<\/w:p>/u', "\n\n", $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function dariPdf(string $path): string
    {
        return (new Parser)->parseFile($path)->getText();
    }

    private function normalisasi(string $teks): string
    {
        $teks = str_replace(["\r\n", "\r", "\u{00A0}"], ["\n", "\n", ' '], $teks);
        $baris = array_map(
            fn (string $baris) => trim((string) preg_replace('/[\t ]+/u', ' ', $baris)),
            explode("\n", $teks),
        );
        $teks = implode("\n", $baris);
        $teks = (string) preg_replace('/\n{3,}/u', "\n\n", $teks);

        return trim($teks);
    }
}
