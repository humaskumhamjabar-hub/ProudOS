<?php

namespace Modules\Work\Models;

use Illuminate\Database\Eloquent\Model;

class TugasBahan extends Model
{
    protected $table = 'tugas_bahan';

    protected $fillable = ['tugas_id', 'path', 'nama_asli', 'mime', 'diunggah_oleh'];
}
