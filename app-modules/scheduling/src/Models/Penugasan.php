<?php

namespace Modules\Scheduling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penugasan extends Model
{
    protected $table = 'penugasans';

    protected $fillable = [
        'user_id', 'tipe', 'mulai_at', 'selesai_at', 'deadline_at',
        'untuk_type', 'untuk_id', 'peran_id', 'pembimbing_id',
        'status', 'digantikan_dari_id', 'dibaca_at', 'diterima_at', 'catatan',
    ];

    protected function casts(): array
    {
        return [
            'mulai_at' => 'datetime',
            'selesai_at' => 'datetime',
            'deadline_at' => 'datetime',
            'dibaca_at' => 'datetime',
            'diterima_at' => 'datetime',
        ];
    }

    public function peran(): BelongsTo
    {
        return $this->belongsTo(PeranProduksi::class, 'peran_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function scopeBelumDikonfirmasi(Builder $query): Builder
    {
        return $query->where('status', 'aktif')->whereNull('diterima_at');
    }

    public function scopeUntukHari(Builder $query, \DateTimeInterface $tanggal): Builder
    {
        return $query->where(function (Builder $q) use ($tanggal) {
            $q->where(fn (Builder $b) => $b->where('tipe', 'berjam')->whereDate('mulai_at', $tanggal))
                ->orWhere(fn (Builder $b) => $b->where('tipe', 'berdeadline')->whereDate('deadline_at', '>=', $tanggal));
        });
    }

    /** Bentrok jam dengan penugasan berjam lain milik user yang sama. */
    public function scopeBentrokDengan(Builder $query, int $userId, \DateTimeInterface $mulai, \DateTimeInterface $selesai): Builder
    {
        return $query->where('user_id', $userId)
            ->where('tipe', 'berjam')
            ->where('status', 'aktif')
            ->where('mulai_at', '<', $selesai)
            ->where('selesai_at', '>', $mulai);
    }
}
