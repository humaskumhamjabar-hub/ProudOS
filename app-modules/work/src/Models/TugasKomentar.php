<?php

namespace Modules\Work\Models;

use Illuminate\Database\Eloquent\Model;

class TugasKomentar extends Model
{
    protected $table = 'tugas_komentar';

    protected $fillable = ['tugas_id', 'user_id', 'isi'];
}
