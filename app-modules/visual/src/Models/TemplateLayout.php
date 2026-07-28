<?php

namespace Modules\Visual\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateLayout extends Model
{
    protected $table = 'template_layout';

    protected $fillable = ['template_visual_id', 'jenis', 'definisi', 'batas_karakter'];

    protected function casts(): array
    {
        return ['definisi' => 'array', 'batas_karakter' => 'array'];
    }
}
