<?php

namespace Modules\People\Contracts;

interface PenyediaStatusOrang
{
    /**
     * TEMBOK ketersediaan: true bila orang sama sekali tidak bisa ditugaskan
     * pada tanggal itu — ada ketidakhadiran aktif, atau di luar masa akses.
     */
    public function terhalangTembok(int $userId, \DateTimeInterface $tanggal): bool;

    /** Ringkasan orang untuk ditampilkan modul lain: ['id', 'nama', 'role_slug', 'magang' => bool]. */
    public function ringkasan(int $userId): ?array;

    /** Daftar user aktif (id => nama), untuk pilihan penugasan. */
    public function daftarAktif(): array;
}
