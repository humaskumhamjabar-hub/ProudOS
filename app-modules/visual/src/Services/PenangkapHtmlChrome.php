<?php

namespace Modules\Visual\Services;

use Modules\Visual\Contracts\PenangkapHtml;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class PenangkapHtmlChrome implements PenangkapHtml
{
    public function tangkap(string $htmlPath, string $pngPath, int $lebar, int $tinggi): void
    {
        $errorTerakhir = '';

        for ($percobaan = 1; $percobaan <= 2; $percobaan++) {
            if ($this->jalankanChrome($htmlPath, $pngPath, $lebar, $tinggi, $errorTerakhir)) {
                return;
            }
        }

        throw new RuntimeException('Chrome gagal menangkap slide: '.$errorTerakhir);
    }

    private function jalankanChrome(string $htmlPath, string $pngPath, int $lebar, int $tinggi, string &$error): bool
    {
        $profilChrome = dirname($pngPath).'/chrome-'.pathinfo($pngPath, PATHINFO_FILENAME).'-'.bin2hex(random_bytes(6));
        @unlink($pngPath);
        $filesystem = new Filesystem;
        $process = null;
        try {
            $process = new Process([
                config('visual.chrome_path', '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'),
                '--headless=new', '--disable-gpu', '--hide-scrollbars', '--no-sandbox', '--disable-dev-shm-usage',
                '--disable-crash-reporter', '--disable-breakpad', '--disable-background-networking', '--disable-component-update',
                '--disable-default-apps', '--disable-extensions', '--disable-sync', '--metrics-recording-only', '--no-first-run',
                '--no-default-browser-check', '--run-all-compositor-stages-before-draw', '--virtual-time-budget=1000', "--user-data-dir={$profilChrome}",
                "--window-size={$lebar},{$tinggi}", '--force-device-scale-factor=1',
                "--screenshot={$pngPath}", 'file://'.$htmlPath,
            ]);
            $selesai = false;
            $process->setTimeout(20)->start();
            while ($process->isRunning()) {
                if (is_file($pngPath) && filesize($pngPath) > 0) {
                    $process->stop(0);
                    $selesai = true;
                    break;
                }
                usleep(100000);
                $process->checkTimeout();
            }
        } catch (ProcessTimedOutException $exception) {
            if (is_file($pngPath) && filesize($pngPath) > 0) {
                return true;
            }

            throw $exception;
        } finally {
            if ($process?->isRunning()) {
                $process->stop(0);
            }
            $filesystem->remove($profilChrome);
        }

        if ($selesai || ($process->isSuccessful() && is_file($pngPath) && filesize($pngPath) > 0)) {
            return true;
        }

        $error = trim($process->getErrorOutput()) ?: 'proses berhenti tanpa menghasilkan PNG';

        return false;
    }
}
