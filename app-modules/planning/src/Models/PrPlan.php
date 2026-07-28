<?php

namespace Modules\Planning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrPlan extends Model
{
    protected $table = 'pr_plans';

    protected $fillable = [
        'nama', 'tema', 'periode_mulai', 'periode_selesai',
        'target_jumlah_konten', 'status', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
            'target_jumlah_konten' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrPlanItem::class, 'pr_plan_id');
    }
}
