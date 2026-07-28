<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Content\Actions\EkstrakTeksDokumen;
use Modules\Content\Models\Bahan;

class EkstrakTeksBahan implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public int $bahanId) {}

    public function handle(EkstrakTeksDokumen $ekstraktor): void
    {
        $bahan = Bahan::find($this->bahanId);

        if (! $bahan || $bahan->tipe !== 'dokumen') {
            return;
        }

        $ekstraktor->handle($bahan);
    }
}
