<?php

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use PNScripts\BroadquillLite\BulkEditAction;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\PostResource;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\PostResource\Pages\ListPosts;

use function Pest\Livewire\livewire;

it('works with the common Filament field types', function (callable $field, string $name, mixed $value, mixed $expected) {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->fields([$field()]);
    $posts = collect([makePost(), makePost()]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => [$name, $value]]))
        ->assertHasNoTableBulkActionErrors();

    foreach ($posts->map->fresh() as $post) {
        $actual = $post->{$name};

        if ($actual instanceof DateTimeInterface) {
            $actual = $actual->format('Y-m-d H:i:s');
        }

        expect($actual)->toEqual($expected);
    }
})->with([
    'Radio' => [fn () => Radio::make('status')->options(['draft' => 'Draft', 'review' => 'Review']), 'status', 'review', 'review'],
    'ToggleButtons' => [fn () => ToggleButtons::make('status')->options(['draft' => 'Draft', 'review' => 'Review']), 'status', 'review', 'review'],
    'Checkbox' => [fn () => Checkbox::make('is_featured'), 'is_featured', true, true],
    'MarkdownEditor' => [fn () => MarkdownEditor::make('notes'), 'notes', '**Back in stock**', '**Back in stock**'],
    'TagsInput on a JSON column' => [fn () => TagsInput::make('labels'), 'labels', ['eco', 'gift'], ['eco', 'gift']],
    'multiple Select on a JSON column' => [fn () => Select::make('labels')->multiple()->options(['eco' => 'Eco', 'gift' => 'Gift']), 'labels', ['gift'], ['gift']],
    'CheckboxList on a JSON column' => [fn () => CheckboxList::make('labels')->options(['eco' => 'Eco', 'gift' => 'Gift']), 'labels', ['eco'], ['eco']],
    'DateTimePicker' => [fn () => DateTimePicker::make('remind_at'), 'remind_at', '2026-11-02 09:30:00', '2026-11-02 09:30:00'],
    'TimePicker' => [fn () => TimePicker::make('opens_at'), 'opens_at', '08:15:00', '08:15:00'],
    'ColorPicker' => [fn () => ColorPicker::make('color'), 'color', '#0ea5e9', '#0ea5e9'],
]);
