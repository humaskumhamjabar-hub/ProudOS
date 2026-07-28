<?php

namespace Modules\Ai\Services;

use LogicException;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\HasilAi;

class PenyediaAiNonaktif implements PenyediaAi
{
    public function tersedia(): bool
    {
        return false;
    }

    public function hasilkan(string $jenis, string $judul, array $sumber): HasilAi
    {
        throw new LogicException('Penyedia AI belum dikonfigurasi.');
    }
}
