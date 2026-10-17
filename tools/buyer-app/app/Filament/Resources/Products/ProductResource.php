<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use PNScripts\BroadquillLite\BulkEditAction;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public const STATUSES = ['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('sku')->label('SKU')->color('gray')->fontFamily('mono'),
                TextColumn::make('category.name')->label('Category'),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUSES[$state])
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'draft' => 'gray',
                        'archived' => 'warning',
                    }),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('price')->money('EUR')->alignEnd(),
                TextColumn::make('stock')->numeric()->alignEnd(),
                TextColumn::make('launch_date')->date('j M Y')->placeholder('Not set'),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::STATUSES),
                SelectFilter::make('category')->relationship('category', 'name'),
            ])
            ->defaultSort('id')
            ->toolbarActions([
                BulkActionGroup::make([
                    self::bulkEdit(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function bulkEdit(): BulkEditAction
    {
        return BulkEditAction::make()->fields([
            Select::make('status')->options(self::STATUSES),
            Select::make('category_id')->label('Category')->relationship('category', 'name'),
            Toggle::make('is_featured')->label('Featured'),
            TextInput::make('price')->numeric()->prefix('€')->minValue(0),
            TextInput::make('stock')->numeric()->integer()->minValue(0),
            DatePicker::make('launch_date'),
            Textarea::make('notes')->rows(2),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListProducts::route('/')];
    }
}
