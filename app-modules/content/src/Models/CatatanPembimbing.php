<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanPembimbing extends Model
{
    protected $table = 'catatan_pembimbing';

    protected $fillable = ['penugasan_id', 'isi', 'oleh_id'];
}
