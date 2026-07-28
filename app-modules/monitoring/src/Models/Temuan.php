<?php

namespace Modules\Monitoring\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Temuan extends Model
{
    protected $table = 'temuan';

    protected $fillable = [
        'sumber', 'ringkasan', 'url', 'sentimen', 'tanggal',
        'status_tindak_lanjut', 'pic_id',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function tindakLanjut(): HasMany
    {
        return $this->hasMany(TindakLanjut::class, 'temuan_id')->latest('at');
    }
}
