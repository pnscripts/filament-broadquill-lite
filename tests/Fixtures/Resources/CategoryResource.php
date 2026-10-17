<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\Category;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\CategoryResource\Pages\EditCategory;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\CategoryResource\Pages\ListCategories;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\CategoryResource\RelationManagers\PostsRelationManager;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')]);
    }

    public static function getRelations(): array
    {
        return [PostsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
