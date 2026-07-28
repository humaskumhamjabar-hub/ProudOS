<?php

namespace Modules\Library\Models;

use Illuminate\Database\Eloquent\Model;

class Pustaka extends Model
{
    protected $table = 'pustaka';

    protected $fillable = ['judul', 'kategori', 'tipe', 'path', 'isi', 'versi', 'dibuat_oleh'];
}
