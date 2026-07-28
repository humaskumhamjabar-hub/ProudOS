<?php

namespace Modules\People\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\People\Contracts\PenyediaStatusOrang;
use Modules\People\Services\StatusOrang;

class PeopleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PenyediaStatusOrang::class, StatusOrang::class);
    }

    public function boot(): void {}
}
