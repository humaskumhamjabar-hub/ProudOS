<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Draf extends Model
{
    protected $table = 'draf';

    protected $fillable = [
        'paket_konten_id', 'jenis', 'isi', 'versi',
        'asal', 'latihan', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return ['latihan' => 'boolean'];
    }

    public function paketKonten(): BelongsTo
    {
        return $this->belongsTo(PaketKonten::class, 'paket_konten_id');
    }
}
