<?php

namespace Modules\Ai\Contracts;

use Modules\Ai\HasilAi;

interface PenyediaAi
{
    public function tersedia(): bool;

    /** @param array<int, string> $sumber */
    public function hasilkan(string $jenis, string $judul, array $sumber): HasilAi;
}
