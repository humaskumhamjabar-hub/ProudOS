<?php

namespace Modules\Monitoring\Models;

use Illuminate\Database\Eloquent\Model;

class TindakLanjut extends Model
{
    protected $table = 'tindak_lanjut';

    protected $fillable = ['temuan_id', 'aksi', 'oleh_id', 'at'];

    protected function casts(): array
    {
        return ['at' => 'datetime'];
    }
}
