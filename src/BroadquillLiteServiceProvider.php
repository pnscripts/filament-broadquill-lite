<?php

declare(strict_types=1);

namespace PNScripts\BroadquillLite;

use Illuminate\Support\ServiceProvider;

final class BroadquillLiteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'broadquill-lite');

        $this->publishes([
            __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/broadquill-lite'),
        ], 'broadquill-lite-translations');
    }
}
