<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures\Resources\CategoryResource\Pages;

use Filament\Resources\Pages\EditRecord;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\CategoryResource;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;
}
