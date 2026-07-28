<?php

namespace Modules\Scheduling\Actions;

use Illuminate\Validation\ValidationException;
use Modules\Scheduling\Models\Penugasan;

class BuatPenugasan
{
    public function __construct(private CekKetersediaan $cek) {}

    /**
     * @param  bool  $terobos  true = koordinator sadar menerobos bentrok jam
     */
    public function handle(array $data, bool $terobos = false): Penugasan
    {
        $mulai = isset($data['mulai_at']) ? new \DateTime($data['mulai_at']) : null;
        $selesai = isset($data['selesai_at']) ? new \DateTime($data['selesai_at']) : null;
        $acuan = $mulai ?? (isset($data['deadline_at']) ? new \DateTime($data['deadline_at']) : now());

        $ketersediaan = $this->cek->handle($data['user_id'], $acuan, $selesai);

        if ($ketersediaan['tembok']) {
            throw ValidationException::withMessages([
                'user_id' => 'Orang ini tidak bisa ditugaskan: ada ketidakhadiran aktif atau di luar masa akses.',
            ]);
        }

        if ($data['tipe'] === 'berjam' && $ketersediaan['bentrok'] !== [] && ! $terobos) {
            throw ValidationException::withMessages([
                'user_id' => 'Bentrok jam dengan penugasan lain. Terobos secara sadar bila memang perlu.',
            ]);
        }

        $penugasan = Penugasan::create($data);

        // Menerobos = penugasan lama tidak dihapus; tandai butuh_pengganti supaya
        // muncul di Beranda koordinator. Override sunyi dilarang oleh rancangan.
        if ($data['tipe'] === 'berjam' && $terobos) {
            foreach ($ketersediaan['bentrok'] as $b) {
                Penugasan::where('id', $b['id'])->update(['status' => 'butuh_pengganti']);
                $penugasan->digantikan_dari_id = $b['id'];
            }
            $penugasan->save();
        }

        return $penugasan;
    }
}
