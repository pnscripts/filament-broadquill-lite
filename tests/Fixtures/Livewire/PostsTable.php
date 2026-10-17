<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use PNScripts\BroadquillLite\BulkEditAction;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\Post;

class PostsTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Post::query())
            ->columns([TextColumn::make('title'), TextColumn::make('status')])
            ->toolbarActions([
                BulkEditAction::make()->fields([
                    Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
                ]),
            ]);
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                {{ $this->table }}
                <x-filament-actions::modals />
            </div>
            BLADE;
    }
}
