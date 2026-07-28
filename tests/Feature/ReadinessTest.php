<?php

use Illuminate\Support\Facades\Storage;

it('melaporkan database dan penyimpanan siap', function () {
    Storage::fake('local');

    $this->getJson(route('ready'))
        ->assertOk()
        ->assertJson([
            'status' => 'siap',
            'checks' => ['database' => true, 'storage' => true],
        ]);
});

it('menggunakan probe penyimpanan unik pada setiap request', function () {
    $written = [];
    $deleted = [];
    $disk = Mockery::mock();
    $disk->shouldReceive('put')->twice()->withArgs(function (string $path) use (&$written) {
        $written[] = $path;

        return true;
    })->andReturnTrue();
    $disk->shouldReceive('exists')->twice()->andReturnTrue();
    $disk->shouldReceive('delete')->twice()->withArgs(function (string $path) use (&$deleted) {
        $deleted[] = $path;

        return true;
    })->andReturnTrue();
    Storage::shouldReceive('disk')->with('local')->andReturn($disk);
    $this->getJson(route('ready'))->assertOk();
    $this->getJson(route('ready'))->assertOk();

    expect($written)->toHaveCount(2)
        ->and($written[0])->toStartWith('.readiness-probe-')
        ->and($written[1])->toStartWith('.readiness-probe-')
        ->and($written[0])->not->toBe($written[1])
        ->and($deleted)->toBe($written)
        ->and($written)->not->toContain('.readiness-probe');
});

it('menyiapkan direktori sumber backup file pada aplikasi baru', function () {
    Storage::fake('local');

    foreach (config('backup.backup.source.files.include') as $direktori) {
        expect(is_dir($direktori))->toBeTrue()
            ->and(file_get_contents("{$direktori}/.gitignore"))->toBe("*\n!.gitignore\n");
    }
});

it('memakai backup luar server secara default di produksi', function () {
    $originalAppEnv = getenv('APP_ENV');
    $originalBackupDisk = getenv('BACKUP_DISK');
    $originalAppEnvServer = $_SERVER['APP_ENV'] ?? null;
    $originalBackupDiskServer = $_SERVER['BACKUP_DISK'] ?? null;
    putenv('APP_ENV=production');
    putenv('BACKUP_DISK');
    $_SERVER['APP_ENV'] = 'production';
    unset($_SERVER['BACKUP_DISK']);

    try {
        $backup = require base_path('config/backup.php');
    } finally {
        putenv($originalAppEnv === false ? 'APP_ENV' : "APP_ENV={$originalAppEnv}");
        putenv($originalBackupDisk === false ? 'BACKUP_DISK' : "BACKUP_DISK={$originalBackupDisk}");
        if ($originalAppEnvServer === null) {
            unset($_SERVER['APP_ENV']);
        } else {
            $_SERVER['APP_ENV'] = $originalAppEnvServer;
        }

        if ($originalBackupDiskServer === null) {
            unset($_SERVER['BACKUP_DISK']);
        } else {
            $_SERVER['BACKUP_DISK'] = $originalBackupDiskServer;
        }
    }

    expect($backup['backup']['destination']['disks'])->toBe(['backup_s3'])
        ->and($backup['monitor_backups'][0]['disks'])->toBe(['backup_s3']);
});
