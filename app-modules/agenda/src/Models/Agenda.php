<?php

namespace Modules\Agenda\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $fillable = [
        'judul', 'deskripsi', 'mulai_at', 'selesai_at', 'lokasi',
        'kebutuhan_humas', 'sumber_type', 'sumber_id', 'status', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'mulai_at' => 'datetime',
            'selesai_at' => 'datetime',
            'kebutuhan_humas' => 'array',
        ];
    }

    public function scopePadaTanggal(Builder $query, \DateTimeInterface $tanggal): Builder
    {
        return $query->whereDate('mulai_at', $tanggal);
    }

    public function scopeBerjalan(Builder $query): Builder
    {
        return $query->whereIn('status', ['rencana', 'berjalan']);
    }
}
