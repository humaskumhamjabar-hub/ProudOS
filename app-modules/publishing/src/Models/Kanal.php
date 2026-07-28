<?php

namespace Modules\Publishing\Models;

use Illuminate\Database\Eloquent\Model;

class Kanal extends Model
{
    protected $table = 'kanal';

    protected $fillable = ['nama', 'jenis', 'aktif'];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }
}
