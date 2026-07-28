<?php

namespace Modules\Visual\Models;

use Illuminate\Database\Eloquent\Model;

class RenderSlide extends Model
{
    protected $table = 'render_slide';

    protected $fillable = [
        'render_id', 'urutan', 'jenis', 'bahan_id', 'posisi_foto', 'isi_teks',
    ];

    protected function casts(): array
    {
        return ['posisi_foto' => 'array', 'isi_teks' => 'array'];
    }
}
