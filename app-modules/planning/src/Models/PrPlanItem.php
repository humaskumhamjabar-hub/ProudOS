<?php

namespace Modules\Planning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrPlanItem extends Model
{
    protected $table = 'pr_plan_items';

    protected $fillable = [
        'pr_plan_id', 'judul', 'catatan', 'rencana_kasar',
        'jenis_output_id', 'kanal_tujuan', 'agenda_id', 'status',
    ];

    protected function casts(): array
    {
        return ['kanal_tujuan' => 'array'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PrPlan::class, 'pr_plan_id');
    }

    public function jenisOutput(): BelongsTo
    {
        return $this->belongsTo(JenisOutput::class, 'jenis_output_id');
    }
}
