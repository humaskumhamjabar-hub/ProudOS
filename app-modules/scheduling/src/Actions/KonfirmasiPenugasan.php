<?php

namespace Modules\Scheduling\Actions;

use Modules\Scheduling\Models\Penugasan;

class KonfirmasiPenugasan
{
    /** Otomatis saat halaman dibuka — bukan tindakan sadar. */
    public function tandaiDibaca(Penugasan $penugasan): void
    {
        if ($penugasan->dibaca_at === null) {
            $penugasan->forceFill(['dibaca_at' => now()])->save();
        }
    }

    /** Tombol "terima" — tindakan sadar, dasar pertanggungjawaban. */
    public function terima(Penugasan $penugasan): void
    {
        if ($penugasan->diterima_at === null) {
            $penugasan->forceFill(['diterima_at' => now()])->save();
        }
    }
}
