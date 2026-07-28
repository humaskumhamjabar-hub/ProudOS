<?php

namespace Modules\Ai\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\Services\PenyediaAiNonaktif;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/ai.php', 'ai');
        $this->app->bind(PenyediaAi::class, PenyediaAiNonaktif::class);
    }
}
