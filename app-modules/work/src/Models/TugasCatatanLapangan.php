<?php

namespace Modules\Work\Models;

use Illuminate\Database\Eloquent\Model;

class TugasCatatanLapangan extends Model
{
    protected $table = 'tugas_catatan_lapangan';

    protected $fillable = [
        'tugas_id', 'laporan_atensi', 'sambutan', 'draf_dasar_narasi', 'dibuat_oleh',
        'usulan_ai', 'model_ai', 'prompt_versi_ai', 'dibuat_ai_at',
        'bahan_website', 'narasi_website_final', 'instruksi_koreksi_website',
        'foto_website_bahan_id', 'foto_website_path', 'foto_website_mime', 'foto_website_disimpan_at',
        'foto_website_items',
        'bahan_sosmed', 'tautan_berita_sosmed', 'caption_sosmed_final', 'instruksi_koreksi_sosmed',
        'usulan_ai_sosmed', 'model_ai_sosmed', 'prompt_versi_ai_sosmed', 'dibuat_ai_sosmed_at',
        'carousel_sosmed_slides', 'carousel_sosmed_disimpan_at',
        'carousel_sosmed_template_id', 'carousel_sosmed_template_versi',
        'video_sosmed_scenes', 'video_sosmed_template_id', 'video_sosmed_template_versi',
        'video_sosmed_status', 'video_sosmed_path', 'video_sosmed_pesan_gagal', 'video_sosmed_disimpan_at',
    ];

    protected function casts(): array
    {
        return [
            'dibuat_ai_at' => 'datetime',
            'foto_website_disimpan_at' => 'datetime',
            'foto_website_items' => 'array',
            'dibuat_ai_sosmed_at' => 'datetime',
            'carousel_sosmed_slides' => 'array',
            'carousel_sosmed_disimpan_at' => 'datetime',
            'video_sosmed_scenes' => 'array',
            'video_sosmed_disimpan_at' => 'datetime',
        ];
    }
}
