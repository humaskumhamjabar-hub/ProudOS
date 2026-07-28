<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Semua cek "boleh atau tidak" lewat izin, tidak pernah lewat slug peran.
        // Return null (bukan false) supaya Gate/Policy lain tetap dievaluasi.
        Gate::before(function ($user, string $ability) {
            return $user->punyaIzin($ability) ?: null;
        });
    }
}
