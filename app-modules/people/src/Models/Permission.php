<?php

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['nama', 'slug'];
}
