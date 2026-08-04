<?php

use Illuminate\Support\Facades\File;
use Modules\Visual\Services\PenangkapHtmlChrome;

it('mengulang tangkapan sekali ketika proses Chrome gagal saat startup', function () {
    $direktori = storage_path('framework/testing/chrome-retry');
    $script = $direktori.'/chrome-palsu';
    $jumlahJalankan = $direktori.'/jumlah-jalankan';
    $logArgumen = $direktori.'/argumen.log';
    $html = $direktori.'/slide.html';
    $png = $direktori.'/slide.png';
    $pngSumber = $direktori.'/sumber.png';

    if (is_dir($direktori)) {
        File::deleteDirectory($direktori);
    }

    File::makeDirectory($direktori, 0755, true);
    try {
        file_put_contents($html, '<!doctype html><title>Slide uji</title>');
        $gambar = imagecreatetruecolor(2, 2);
        imagepng($gambar, $pngSumber);
        imagedestroy($gambar);

        file_put_contents($script, sprintf(<<<'SH'
#!/bin/sh
counter=%s
arguments=%s
source_png=%s
count=0
if [ -f "$counter" ]; then
    count=$(cat "$counter")
fi
count=$((count + 1))
printf '%%s' "$count" > "$counter"
printf '%%s\n' "$*" >> "$arguments"
if [ "$count" -eq 1 ]; then
    printf 'Abort trap: 6\n' >&2
    exit 134
fi
for argument in "$@"; do
    case "$argument" in
        --screenshot=*) output=${argument#--screenshot=} ;;
    esac
done
cp "$source_png" "$output"
SH,
            escapeshellarg($jumlahJalankan),
            escapeshellarg($logArgumen),
            escapeshellarg($pngSumber),
        ));
        chmod($script, 0755);
        config()->set('visual.chrome_path', $script);

        (new PenangkapHtmlChrome)->tangkap($html, $png, 1080, 1350);

        expect(file_get_contents($jumlahJalankan))->toBe('2')
            ->and($png)->toBeFile()
            ->and(filesize($png))->toBeGreaterThan(0);

        $profil = collect(file($logArgumen, FILE_IGNORE_NEW_LINES))
            ->map(function (string $argumen): string {
                preg_match('/--user-data-dir=([^ ]+)/', $argumen, $hasil);

                return $hasil[1] ?? '';
            });

        expect($profil)->toHaveCount(2)
            ->and($profil->unique())->toHaveCount(2);

        $profil->each(fn (string $path) => expect($path)->not->toBeDirectory());
    } finally {
        File::deleteDirectory($direktori);
    }
});
