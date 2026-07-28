<?php

namespace Modules\Scheduling\Models;

use Illuminate\Database\Eloquent\Model;

class PeranProduksi extends Model
{
    protected $table = 'peran_produksi';

    protected $fillable = ['nama', 'slug', 'aktif'];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }
}
