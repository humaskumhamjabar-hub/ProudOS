<?php

namespace Modules\People\Services;

use Illuminate\Support\Carbon;
use Modules\People\Contracts\PenyediaStatusOrang;
use Modules\People\Models\Ketidakhadiran;
use Modules\People\Models\User;

class StatusOrang implements PenyediaStatusOrang
{
    public function terhalangTembok(int $userId, \DateTimeInterface $tanggal): bool
    {
        $user = User::with('role')->find($userId);

        if (! $user || $user->status !== 'aktif') {
            return true;
        }

        $t = Carbon::parse($tanggal->format('Y-m-d'));

        if ($user->aktif_mulai && $t->lt($user->aktif_mulai)) {
            return true;
        }

        if ($user->aktif_sampai && $t->gt($user->aktif_sampai)) {
            return true;
        }

        return Ketidakhadiran::where('user_id', $userId)->aktifPada($tanggal)->exists();
    }

    public function ringkasan(int $userId): ?array
    {
        $user = User::with('role')->find($userId);

        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'nama' => $user->nama,
            'role_slug' => $user->role?->slug,
            'magang' => $user->role?->slug === 'magang',
        ];
    }

    public function daftarAktif(): array
    {
        return User::where('status', 'aktif')
            ->where(fn ($q) => $q->whereNull('aktif_sampai')->orWhereDate('aktif_sampai', '>=', now()))
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }
}
