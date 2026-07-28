<?php

namespace Modules\Visual\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Visual\Contracts\PenangkapHtml;
use Modules\Visual\Services\PenangkapHtmlChrome;

class VisualServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/visual.php', 'visual');
        $this->app->bind(PenangkapHtml::class, PenangkapHtmlChrome::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'visual');
    }
}
