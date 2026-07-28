<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bahan extends Model
{
    protected $table = 'bahan';

    protected $fillable = [
        'paket_konten_id', 'tipe', 'path', 'nama_asli', 'mime',
        'teks_terekstrak', 'status_ekstraksi', 'dipakai_final',
        'diunggah_oleh', 'urutan',
    ];

    protected function casts(): array
    {
        return ['dipakai_final' => 'boolean'];
    }

    public function paketKonten(): BelongsTo
    {
        return $this->belongsTo(PaketKonten::class, 'paket_konten_id');
    }
}
