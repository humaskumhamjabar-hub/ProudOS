<?php

namespace Modules\Ai\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\Services\PenyediaAiNonaktif;
use Modules\Ai\Services\PenyediaAiOpenAiCompatible;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/ai.php', 'ai');
        $this->app->bind(PenyediaAi::class, function ($app): PenyediaAi {
            if (config('ai.provider') === 'openai_compatible') {
                return $app->make(PenyediaAiOpenAiCompatible::class);
            }

            return $app->make(PenyediaAiNonaktif::class);
        });
    }
}
