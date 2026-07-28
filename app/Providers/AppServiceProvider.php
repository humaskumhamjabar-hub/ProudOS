<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\People\Models\User;
use Modules\Scheduling\Models\Penugasan;
use Modules\Work\Models\Tugas;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (config('backup.backup.source.files.include', []) as $direktori) {
            if (! is_dir($direktori)) {
                mkdir($direktori, 0755, true);
            }
            if (! file_exists("{$direktori}/.gitignore")) {
                file_put_contents("{$direktori}/.gitignore", "*\n!.gitignore\n");
            }
        }

        // Semua cek "boleh atau tidak" lewat izin, tidak pernah lewat slug peran.
        // Return null (bukan false) supaya Gate/Policy lain tetap dievaluasi.
        Gate::before(function ($user, string $ability) {
            return $user->punyaIzin($ability) ?: null;
        });

        Gate::define('lihat-tugas', function (User $user, Tugas $tugas): bool {
            if ($user->punyaIzin('kelola_tugas')) {
                return true;
            }

            return Penugasan::query()
                ->where(fn ($query) => $query->where('user_id', $user->id)->orWhere('pembimbing_id', $user->id))
                ->where('untuk_type', 'tugas')
                ->where('untuk_id', $tugas->id)
                ->where('status', '!=', 'batal')
                ->exists();
        });

        Gate::define('kerjakan-tugas', function (User $user, Tugas $tugas): bool {
            if ($user->punyaIzin('kelola_tugas')) {
                return true;
            }

            return Penugasan::query()
                ->where('user_id', $user->id)
                ->where('untuk_type', 'tugas')
                ->where('untuk_id', $tugas->id)
                ->where('status', 'aktif')
                ->exists();
        });
    }
}
