<?php

namespace PNScripts\BroadquillLite\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use PNScripts\BroadquillLite\BroadquillLiteServiceProvider;
use PNScripts\BroadquillLite\Support\ColumnInspector;
use PNScripts\BroadquillLite\Tests\Fixtures\AdminPanelProvider;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\Post;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\User;
use PNScripts\BroadquillLite\Tests\Fixtures\Policies\PostPolicy;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\PostResource;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ColumnInspector::flush();
        PostResource::$configureBulkEdit = null;
        Post::$cancelSaveForTitle = null;
        Post::$throwForTitle = null;
        Post::$savedTitles = [];

        Gate::policy(Post::class, PostPolicy::class);

        $this->actingAs(User::query()->create([
            'name' => 'Editor',
            'email' => 'editor@example.test',
            'password' => 'not-used',
        ]));
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            AdminPanelProvider::class,
            BroadquillLiteServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('b', 32)));
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
