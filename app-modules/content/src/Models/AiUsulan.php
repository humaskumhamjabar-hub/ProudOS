<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsulan extends Model
{
    protected $table = 'ai_usulan';

    protected $fillable = [
        'paket_konten_id', 'jenis', 'isi', 'status',
        'ditinjau_oleh', 'ditinjau_at', 'model', 'prompt_versi',
    ];

    protected function casts(): array
    {
        return ['ditinjau_at' => 'datetime'];
    }

    public function paketKonten(): BelongsTo
    {
        return $this->belongsTo(PaketKonten::class, 'paket_konten_id');
    }
}
