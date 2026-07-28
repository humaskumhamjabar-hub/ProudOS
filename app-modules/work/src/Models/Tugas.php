<?php

namespace Modules\Work\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tugas extends Model
{
    protected $table = 'tugas';

    protected $fillable = [
        'judul', 'brief', 'deadline_at', 'status',
        'agenda_id', 'subjek_type', 'subjek_id', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'datetime',
        ];
    }

    public function bahan(): HasMany
    {
        return $this->hasMany(TugasBahan::class, 'tugas_id');
    }

    public function komentar(): HasMany
    {
        return $this->hasMany(TugasKomentar::class, 'tugas_id');
    }
}
