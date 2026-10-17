<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures\Resources\CategoryResource\Pages;

use Filament\Resources\Pages\ListRecords;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\CategoryResource;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;
}
