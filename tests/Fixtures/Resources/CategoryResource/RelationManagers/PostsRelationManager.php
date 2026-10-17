<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures\Resources\CategoryResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PNScripts\BroadquillLite\BulkEditAction;

class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    public function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('title')->required()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([TextColumn::make('title'), TextColumn::make('status')])
            ->toolbarActions([
                BulkEditAction::make()->fields([
                    Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
                ]),
            ]);
    }
}
