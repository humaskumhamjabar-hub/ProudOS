<?php

namespace Modules\Ai\Models;

use Illuminate\Database\Eloquent\Model;

class KonfigurasiAi extends Model
{
    protected $table = 'konfigurasi_ai';

    protected $fillable = [
        'provider', 'base_url', 'api_key', 'model', 'timeout', 'prompt_versi', 'diubah_oleh',
    ];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'timeout' => 'integer',
        ];
    }
}
