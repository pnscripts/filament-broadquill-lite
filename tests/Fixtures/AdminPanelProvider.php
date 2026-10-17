<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\PostResource;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->resources([PostResource::class, Resources\CategoryResource::class]);
    }
}
