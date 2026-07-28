<?php

namespace Modules\Scheduling\Actions;

use Modules\People\Contracts\PenyediaStatusOrang;
use Modules\Scheduling\Models\Penugasan;

/**
 * Aturan ketersediaan — hanya tiga baris:
 *   TEMBOK   ketidakhadiran aktif / di luar masa akses  → tidak bisa dipilih
 *   TAHAN    bentrok jam antar penugasan berjam          → bisa diterobos sadar
 *   INFO     jumlah penugasan berdeadline yang dipegang  → hanya ditampilkan
 */
class CekKetersediaan
{
    public function __construct(private PenyediaStatusOrang $orang) {}

    /**
     * @return array{tembok: bool, bentrok: array, beban_deadline: int}
     */
    public function handle(int $userId, \DateTimeInterface $mulai, ?\DateTimeInterface $selesai = null): array
    {
        $tembok = $this->orang->terhalangTembok($userId, $mulai);

        $bentrok = [];
        if (! $tembok && $selesai) {
            $bentrok = Penugasan::query()
                ->bentrokDengan($userId, $mulai, $selesai)
                ->get(['id', 'mulai_at', 'selesai_at', 'untuk_type', 'untuk_id'])
                ->toArray();
        }

        $bebanDeadline = Penugasan::query()
            ->where('user_id', $userId)
            ->where('tipe', 'berdeadline')
            ->where('status', 'aktif')
            ->count();

        return [
            'tembok' => $tembok,
            'bentrok' => $bentrok,
            'beban_deadline' => $bebanDeadline,
        ];
    }
}
