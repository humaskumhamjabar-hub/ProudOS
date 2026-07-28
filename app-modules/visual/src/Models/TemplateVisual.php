<?php

namespace Modules\Visual\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateVisual extends Model
{
    protected $table = 'template_visual';

    protected $fillable = [
        'nama', 'format', 'rasio', 'versi', 'status',
        'durasi_per_slide_detik', 'dibuat_oleh',
    ];

    public function layouts(): HasMany
    {
        return $this->hasMany(TemplateLayout::class, 'template_visual_id');
    }

    public function aset(): HasMany
    {
        return $this->hasMany(TemplateAset::class, 'template_visual_id');
    }
}
