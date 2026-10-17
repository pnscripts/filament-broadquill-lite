<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures\Resources;

use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use PNScripts\BroadquillLite\BulkEditAction;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\Post;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\PostResource\Pages\ListPosts;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    /** Lets a test reconfigure the bulk edit action. */
    public static ?Closure $configureBulkEdit = null;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $action = BulkEditAction::make()
            ->fields([
                Select::make('status')->options([
                    'draft' => 'Draft',
                    'review' => 'In review',
                    'published' => 'Published',
                ]),
                Select::make('category_id')->label('Category')->relationship('category', 'name'),
                Toggle::make('is_featured')->label('Featured'),
                DatePicker::make('published_on'),
                TextInput::make('price')->numeric()->minValue(0),
                Textarea::make('notes'),
            ]);

        if (static::$configureBulkEdit) {
            (static::$configureBulkEdit)($action);
        }

        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('status'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([$action]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
        ];
    }
}
