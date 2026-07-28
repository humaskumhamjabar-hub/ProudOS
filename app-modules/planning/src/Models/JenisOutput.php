<?php

namespace Modules\Planning\Models;

use Illuminate\Database\Eloquent\Model;

class JenisOutput extends Model
{
    protected $table = 'jenis_outputs';

    protected $fillable = ['nama', 'slug', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}
