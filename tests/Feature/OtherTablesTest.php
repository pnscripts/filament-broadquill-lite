<?php

use PNScripts\BroadquillLite\Tests\Fixtures\Livewire\PostsTable;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\Category;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\CategoryResource\Pages\EditCategory;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\CategoryResource\RelationManagers\PostsRelationManager;

use function Pest\Livewire\livewire;

it('works in a relation manager and checks the update policy there', function () {
    $category = Category::query()->create(['name' => 'News']);
    $open = makePost(['category_id' => $category->id]);
    $locked = makePost(['category_id' => $category->id, 'is_locked' => true]);

    livewire(PostsRelationManager::class, ['ownerRecord' => $category, 'pageClass' => EditCategory::class])
        ->callTableBulkAction('bulkEdit', [$open, $locked], data: bulkEditData([0 => ['status', 'published']]));

    expect($open->fresh()->status)->toBe('published')
        ->and($locked->fresh()->status)->toBe('draft');
});

it('works in a standalone Livewire table and uses the model policy', function () {
    $open = makePost();
    $locked = makePost(['is_locked' => true]);

    livewire(PostsTable::class)
        ->callTableBulkAction('bulkEdit', [$open, $locked], data: bulkEditData([0 => ['status', 'published']]));

    expect($open->fresh()->status)->toBe('published')
        ->and($locked->fresh()->status)->toBe('draft')
        ->and(sentNotifications()[0]['title'])->toBe('Applied to 1 of 2');
});
