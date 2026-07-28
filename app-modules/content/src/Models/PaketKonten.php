<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaketKonten extends Model
{
    protected $table = 'paket_konten';

    protected $fillable = [
        'agenda_id', 'pr_plan_item_id', 'judul', 'subjudul',
        'status', 'revisi_ke', 'dibuat_oleh',
    ];

    public function bahan(): HasMany
    {
        return $this->hasMany(Bahan::class, 'paket_konten_id');
    }

    public function draf(): HasMany
    {
        return $this->hasMany(Draf::class, 'paket_konten_id');
    }

    public function aiUsulan(): HasMany
    {
        return $this->hasMany(AiUsulan::class, 'paket_konten_id');
    }
}
