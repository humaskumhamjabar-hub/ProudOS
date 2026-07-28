<?php

namespace Modules\Visual\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Render extends Model
{
    protected $table = 'render';

    protected $fillable = [
        'paket_konten_id', 'template_visual_id', 'template_versi',
        'format', 'status', 'path_hasil', 'dikerjakan_at',
        'selesai_at', 'pesan_gagal',
    ];

    protected function casts(): array
    {
        return ['dikerjakan_at' => 'datetime', 'selesai_at' => 'datetime'];
    }

    public function slides(): HasMany
    {
        return $this->hasMany(RenderSlide::class, 'render_id')->orderBy('urutan');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateVisual::class, 'template_visual_id');
    }
}
