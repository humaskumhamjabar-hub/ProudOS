<?php

namespace Modules\Publishing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Publikasi extends Model
{
    protected $table = 'publikasi';

    protected $fillable = [
        'paket_konten_id', 'kanal_id', 'tayang_at', 'url', 'evidence_path',
        'pic_id', 'diubah_setelah_tayang', 'alasan_perubahan', 'diminta_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tayang_at' => 'datetime',
            'diubah_setelah_tayang' => 'boolean',
        ];
    }

    public function kanal(): BelongsTo
    {
        return $this->belongsTo(Kanal::class, 'kanal_id');
    }
}
