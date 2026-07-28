<?php

namespace Modules\Visual\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateAset extends Model
{
    protected $table = 'template_aset';

    protected $fillable = ['template_visual_id', 'jenis', 'path'];
}
