<?php

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $fillable = ['nama', 'mulai', 'selesai'];

    protected function casts(): array
    {
        return [
            'mulai' => 'date',
            'selesai' => 'date',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
