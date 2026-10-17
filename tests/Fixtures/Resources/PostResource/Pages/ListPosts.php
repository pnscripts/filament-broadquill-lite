<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures\Resources\PostResource\Pages;

use Filament\Resources\Pages\ListRecords;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\PostResource;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;
}
