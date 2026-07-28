<?php

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Model;

class AksesLog extends Model
{
    protected $table = 'akses_log';

    protected $fillable = ['user_id', 'aktif_sampai_lama', 'aktif_sampai_baru', 'oleh_id', 'alasan'];

    protected function casts(): array
    {
        return [
            'aktif_sampai_lama' => 'date',
            'aktif_sampai_baru' => 'date',
        ];
    }
}
