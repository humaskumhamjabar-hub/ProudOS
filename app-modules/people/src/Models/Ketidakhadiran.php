<?php

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Ketidakhadiran extends Model
{
    protected $table = 'ketidakhadiran';

    protected $fillable = ['user_id', 'jenis', 'mulai', 'selesai', 'catatan', 'dicatat_oleh'];

    protected function casts(): array
    {
        return [
            'mulai' => 'date',
            'selesai' => 'date',
        ];
    }

    public function scopeAktifPada(Builder $query, \DateTimeInterface $tanggal): Builder
    {
        return $query->whereDate('mulai', '<=', $tanggal)->whereDate('selesai', '>=', $tanggal);
    }
}
