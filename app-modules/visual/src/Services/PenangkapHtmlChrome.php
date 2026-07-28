<?php

namespace Modules\Visual\Services;

use Modules\Visual\Contracts\PenangkapHtml;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class PenangkapHtmlChrome implements PenangkapHtml
{
    public function tangkap(string $htmlPath, string $pngPath, int $lebar, int $tinggi): void
    {
        $profilChrome = dirname($pngPath).'/chrome-'.pathinfo($pngPath, PATHINFO_FILENAME);
        $process = new Process([
            config('visual.chrome_path', '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'),
            '--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-sandbox', '--disable-dev-shm-usage',
            '--disable-crash-reporter', '--disable-breakpad', "--user-data-dir={$profilChrome}",
            "--window-size={$lebar},{$tinggi}", '--force-device-scale-factor=1',
            "--screenshot={$pngPath}", 'file://'.$htmlPath,
        ]);
        try {
            $process->setTimeout(15)->run();
        } catch (ProcessTimedOutException $exception) {
            if (is_file($pngPath) && filesize($pngPath) > 0) {
                return;
            }

            throw $exception;
        }

        if ((! $process->isSuccessful()) && (! is_file($pngPath) || filesize($pngPath) === 0)) {
            throw new RuntimeException('Chrome gagal menangkap slide: '.$process->getErrorOutput());
        }
    }
}
