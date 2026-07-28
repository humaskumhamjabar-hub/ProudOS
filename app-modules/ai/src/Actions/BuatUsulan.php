<?php

namespace Modules\Ai\Actions;

use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\HasilAi;

class BuatUsulan
{
    public function __construct(private readonly PenyediaAi $penyedia) {}

    /** @param array<int, string> $sumber */
    public function handle(string $jenis, string $judul, array $sumber): HasilAi
    {
        return $this->penyedia->hasilkan($jenis, $judul, $sumber);
    }
}
