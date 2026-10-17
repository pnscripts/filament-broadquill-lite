<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    protected $guarded = [];

    protected $casts = ['is_admin' => 'boolean'];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
