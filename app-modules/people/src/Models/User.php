<?php

namespace Modules\People\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'nama', 'email', 'password',
        'role_id', 'aktif_mulai', 'aktif_sampai', 'batch_id', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif_mulai' => 'date',
            'aktif_sampai' => 'date',
        ];
    }

    /** Inisial nama untuk avatar di header/sidebar. */
    public function initials(): string
    {
        return Str::of($this->nama)
            ->explode(' ')
            ->take(2)
            ->map(fn ($kata) => Str::substr($kata, 0, 1))
            ->implode('');
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function izinTambahan(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission');
    }

    public function ketidakhadiran()
    {
        return $this->hasMany(Ketidakhadiran::class);
    }

    /** Masa akses masih berlaku hari ini (magang kedaluwarsa = false). */
    public function masaAksesAktif(): bool
    {
        $today = now()->startOfDay();

        if ($this->aktif_mulai && $today->lt($this->aktif_mulai)) {
            return false;
        }

        if ($this->aktif_sampai && $today->gt($this->aktif_sampai)) {
            return false;
        }

        return $this->status === 'aktif';
    }

    /** Cek izin lewat peran + izin tambahan. Jangan pernah cek slug peran langsung. */
    public function punyaIzin(string $slug): bool
    {
        if ($this->role?->permissions->contains('slug', $slug)) {
            return true;
        }

        return $this->izinTambahan->contains('slug', $slug);
    }
}
