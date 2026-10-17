<?php

use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use PNScripts\BroadquillLite\BulkEditAction;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\Category;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\Post;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\PostResource;
use PNScripts\BroadquillLite\Tests\Fixtures\Resources\PostResource\Pages\ListPosts;

use function Pest\Livewire\livewire;

// Field indexes in PostResource: 0 status, 1 category_id, 2 is_featured, 3 published_on, 4 price, 5 notes.

it('is registered as a bulk action named bulkEdit with a clear label', function () {
    livewire(ListPosts::class)
        ->assertTableBulkActionExists('bulkEdit')
        ->assertTableBulkActionExists('bulkEdit', fn (BulkEditAction $action): bool => $action->getLabel() === 'Edit selected');
});

it('renders one row per editable field in the modal', function () {
    $posts = collect([makePost(), makePost()]);

    livewire(ListPosts::class)
        ->mountTableBulkAction('bulkEdit', $posts)
        ->assertMountedActionModalSee([
            'Edit selected Posts',
            'Tick each field you want to change.',
            'Keeps its current value',
            'Status',
            'Category',
            'Featured',
            'Published on',
            'Price',
            'Notes',
            'Apply changes',
        ]);
});

it('changes only the ticked field and leaves every other field alone', function () {
    $category = Category::query()->create(['name' => 'News']);
    $posts = collect([
        makePost(['status' => 'draft', 'price' => 10, 'notes' => 'keep me', 'category_id' => $category->id]),
        makePost(['status' => 'review', 'price' => 20, 'notes' => null]),
    ]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => ['status', 'published']]))
        ->assertHasNoTableBulkActionErrors();

    [$a, $b] = $posts->map->fresh()->all();

    expect($a->status)->toBe('published')
        ->and($a->price)->toBe('10.00')
        ->and($a->notes)->toBe('keep me')
        ->and($a->category_id)->toBe($category->id)
        ->and($b->status)->toBe('published')
        ->and($b->price)->toBe('20.00')
        ->and($b->notes)->toBeNull();
});

it('changes several fields of several records in one run', function () {
    $category = Category::query()->create(['name' => 'Guides']);
    $posts = collect([makePost(), makePost(), makePost()]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([
            0 => ['status', 'review'],
            1 => ['category_id', $category->id],
            2 => ['is_featured', true],
            3 => ['published_on', '2026-11-02'],
            4 => ['price', '19.90'],
        ]))
        ->assertHasNoTableBulkActionErrors();

    foreach ($posts->map->fresh() as $post) {
        expect($post->status)->toBe('review')
            ->and($post->category_id)->toBe($category->id)
            ->and($post->is_featured)->toBeTrue()
            ->and($post->published_on->toDateString())->toBe('2026-11-02')
            ->and($post->price)->toBe('19.90');
    }

    expect(sentNotifications())->toContain([
        'title' => 'Changes applied',
        'body' => '3 records updated.',
        'status' => 'success',
    ]);
});

it('does not touch records that are not selected', function () {
    $selected = makePost(['status' => 'draft']);
    $other = makePost(['status' => 'draft']);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$selected], data: bulkEditData([0 => ['status', 'published']]));

    expect($selected->fresh()->status)->toBe('published')
        ->and($other->fresh()->status)->toBe('draft');
});

it('sets a boolean field to false', function () {
    $posts = collect([makePost(['is_featured' => true]), makePost(['is_featured' => true])]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([2 => ['is_featured', false]]));

    expect($posts->map->fresh()->pluck('is_featured')->all())->toBe([false, false]);
});

it('clears nullable columns', function () {
    $category = Category::query()->create(['name' => 'Old']);
    $posts = collect([
        makePost(['notes' => 'a', 'published_on' => '2026-01-01', 'category_id' => $category->id]),
        makePost(['notes' => 'b', 'published_on' => '2026-02-01', 'category_id' => $category->id]),
    ]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([
            1 => ['category_id', null, 'clear'],
            3 => ['published_on', null, 'clear'],
            5 => ['notes', null, 'clear'],
        ]))
        ->assertHasNoTableBulkActionErrors();

    foreach ($posts->map->fresh() as $post) {
        expect($post->notes)->toBeNull()
            ->and($post->published_on)->toBeNull()
            ->and($post->category_id)->toBeNull();
    }
});

it('offers Clear only for nullable, non-boolean columns', function () {
    livewire(ListPosts::class)
        ->assertTableBulkActionExists('bulkEdit', function (BulkEditAction $action): bool {
            $clearable = collect($action->getEditableFields())
                ->mapWithKeys(fn ($field) => [$field->getName() => $action->isFieldClearable($field)])
                ->all();

            return $clearable === [
                'status' => false,
                'category_id' => true,
                'is_featured' => false,
                'published_on' => true,
                'price' => false,
                'notes' => true,
            ];
        });
});

it('ignores a forged Clear for a NOT NULL column and sets the value instead', function () {
    $post = makePost(['status' => 'draft']);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: bulkEditData([0 => ['status', 'review', 'clear']]));

    expect($post->fresh()->status)->toBe('review');
});

it('requires a value for a ticked NOT NULL column', function () {
    $post = makePost(['status' => 'draft']);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: bulkEditData([0 => ['status', null]]))
        ->assertHasTableBulkActionErrors(['status' => 'required']);

    expect($post->fresh()->status)->toBe('draft');
});

it('keeps the validation rules of the developer\'s field', function () {
    $post = makePost(['price' => 5]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: bulkEditData([4 => ['price', -3]]))
        ->assertHasTableBulkActionErrors(['price']);

    expect($post->fresh()->price)->toBe('5.00');
});

it('does not validate fields that are not ticked', function () {
    $post = makePost(['status' => 'draft', 'price' => 5]);

    $data = bulkEditData([0 => ['status', 'published']]);
    $data['price'] = -100;

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: $data)
        ->assertHasNoTableBulkActionErrors();

    expect($post->fresh()->status)->toBe('published')
        ->and($post->fresh()->price)->toBe('5.00');
});

it('warns and keeps the modal open when no field is ticked', function () {
    $post = makePost(['status' => 'draft']);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: ['status' => 'published'])
        ->assertActionHalted(TestAction::make('bulkEdit')->table()->bulk());

    expect($post->fresh()->status)->toBe('draft')
        ->and(sentNotifications())->toContain([
            'title' => 'Nothing to change',
            'body' => 'Tick at least one field, then apply.',
            'status' => 'warning',
        ]);
});

it('skips records the update policy denies and says how many', function () {
    $open = collect([makePost(['status' => 'draft']), makePost(['status' => 'draft'])]);
    $locked = makePost(['status' => 'draft', 'is_locked' => true]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $open->push($locked), data: bulkEditData([0 => ['status', 'published']]));

    expect($locked->fresh()->status)->toBe('draft')
        ->and(Post::query()->where('status', 'published')->count())->toBe(2);

    $notification = sentNotifications()[0];

    expect($notification['title'])->toBe('Applied to 2 of 3')
        ->and($notification['status'])->toBe('warning')
        ->and($notification['body'])->toContain('You are not allowed to edit 1 record.');
});

it('reports records that already had the new value instead of saving them again', function () {
    $posts = collect([
        makePost(['status' => 'published']),
        makePost(['status' => 'published']),
        makePost(['status' => 'draft']),
    ]);
    Post::$savedTitles = [];

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => ['status', 'published']]));

    expect(Post::$savedTitles)->toBe([$posts[2]->title])
        ->and(sentNotifications())->toContain([
            'title' => 'Changes applied',
            'body' => '1 record updated. 2 records already had these values.',
            'status' => 'success',
        ]);
});

it('saves through Eloquent so model events and casts run', function () {
    $post = makePost(['status' => 'draft']);
    $before = $post->updated_at;
    $this->travel(5)->minutes();
    Post::$savedTitles = [];

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: bulkEditData([0 => ['status', 'review']]));

    expect(Post::$savedTitles)->toBe([$post->title])
        ->and($post->fresh()->updated_at->greaterThan($before))->toBeTrue();
});

it('counts a save cancelled by a model event as not saved and carries on', function () {
    $posts = collect([makePost(), makePost(), makePost()]);
    Post::$cancelSaveForTitle = $posts[1]->title;

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => ['status', 'review']]));

    expect($posts->map->fresh()->pluck('status')->all())->toBe(['review', 'draft', 'review']);

    $notification = sentNotifications()[0];

    expect($notification['title'])->toBe('Applied to 2 of 3')
        ->and($notification['body'])->toContain('1 record could not be saved.');
});

it('carries on after a database error on one record', function () {
    $posts = collect([makePost(), makePost(), makePost()]);
    Post::$throwForTitle = $posts[0]->title;

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => ['status', 'review']]));

    expect($posts->map->fresh()->pluck('status')->all())->toBe(['draft', 'review', 'review'])
        ->and(sentNotifications()[0]['title'])->toBe('Applied to 2 of 3');
});

it('rolls every change back in atomic mode when one record fails', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->atomic();
    $posts = collect([makePost(), makePost(), makePost()]);
    Post::$throwForTitle = $posts[2]->title;

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => ['status', 'review']]));

    expect($posts->map->fresh()->pluck('status')->all())->toBe(['draft', 'draft', 'draft']);

    $notification = sentNotifications()[0];

    expect($notification['title'])->toBe('No changes applied')
        ->and($notification['status'])->toBe('danger')
        ->and($notification['body'])->toContain('every change was rolled back');
});

it('rolls back in atomic mode when the policy denies one record', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->atomic();
    $posts = collect([makePost(), makePost(['is_locked' => true])]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => ['status', 'review']]));

    expect($posts->map->fresh()->pluck('status')->all())->toBe(['draft', 'draft'])
        ->and(sentNotifications()[0]['title'])->toBe('No changes applied');
});

it('rolls back in atomic mode when a model event cancels a save', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->atomic();
    $posts = collect([makePost(), makePost()]);
    Post::$cancelSaveForTitle = $posts[1]->title;

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => ['status', 'review']]));

    expect($posts->map->fresh()->pluck('status')->all())->toBe(['draft', 'draft']);
});

it('applies everything in atomic mode when nothing fails', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->atomic();
    $posts = collect([makePost(), makePost()]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => ['status', 'review']]));

    expect($posts->map->fresh()->pluck('status')->all())->toBe(['review', 'review'])
        ->and(sentNotifications()[0]['title'])->toBe('Changes applied');
});

it('lets the developer adjust the changes per record', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->mutateChangesUsing(
        fn (array $changes, Post $record): array => [...$changes, 'updated_by' => auth()->user()->name . ' via bulk edit of ' . $record->title],
    );
    $post = makePost();

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: bulkEditData([0 => ['status', 'review']]));

    expect($post->fresh()->updated_by)->toBe("Editor via bulk edit of {$post->title}");
});

it('calls afterRecordUpdated only for records that really changed', function () {
    $seen = new ArrayObject;
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->afterRecordUpdated(
        function (Post $record, array $changes) use ($seen): void {
            $seen[] = [$record->title, $changes];
        },
    );
    $changed = makePost(['status' => 'draft']);
    $same = makePost(['status' => 'review']);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$changed, $same], data: bulkEditData([0 => ['status', 'review']]));

    expect($seen->getArrayCopy())->toBe([[$changed->title, ['status' => 'review']]]);
});

it('writes one key inside a JSON column and keeps the other keys', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->fields([
        TextInput::make('settings.color'),
    ]);
    $post = makePost(['settings' => ['color' => 'red', 'size' => 'large']]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: bulkEditData([0 => ['settings.color', 'teal']]))
        ->assertHasNoTableBulkActionErrors();

    expect($post->fresh()->settings)->toBe(['color' => 'teal', 'size' => 'large']);
});

it('explains which attribute is missing from $fillable', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->fields([
        TextInput::make('secret'),
    ]);
    $post = makePost();

    expect(fn () => livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: bulkEditData([0 => ['secret', 'x']])))
        ->toThrow(LogicException::class, '[secret] is not mass assignable');

    expect($post->fresh()->secret)->toBeNull();
});

it('refuses many-to-many relationship fields with a clear message', function () {
    $action = BulkEditAction::make()->fields([
        CheckboxList::make('tags')->relationship('tags', 'name'),
    ]);

    expect(fn () => $action->getEditableFields())->toThrow(LogicException::class, 'many-to-many');

    $select = BulkEditAction::make()->fields([
        Select::make('tags')->multiple()->relationship('tags', 'name'),
    ]);

    expect(fn () => $select->getEditableFields())->toThrow(LogicException::class, 'many-to-many');
});

it('refuses the same field twice', function () {
    $action = BulkEditAction::make()->fields([Textarea::make('notes'), TextInput::make('notes')]);

    expect(fn () => $action->getEditableFields())->toThrow(LogicException::class, 'listed twice');
});

it('refuses entries that are not form fields', function () {
    $action = BulkEditAction::make()->fields(['status']);

    expect(fn () => $action->getEditableFields())->toThrow(LogicException::class, 'must be a Filament form field');
});

it('lets the developer choose which fields can be cleared', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->clearable(['notes']);

    livewire(ListPosts::class)
        ->assertTableBulkActionExists('bulkEdit', function (BulkEditAction $action): bool {
            return collect($action->getEditableFields())
                ->filter(fn ($field) => $action->isFieldClearable($field))
                ->map->getName()
                ->values()
                ->all() === ['notes'];
        });

    $post = makePost(['published_on' => '2026-01-01']);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: bulkEditData([3 => ['published_on', '2026-03-03', 'clear']]));

    expect($post->fresh()->published_on->toDateString())->toBe('2026-03-03');
});

it('turns Clear off for every field with clearable(false)', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->clearable(false);

    livewire(ListPosts::class)
        ->assertTableBulkActionExists('bulkEdit', fn (BulkEditAction $action): bool => collect($action->getEditableFields())
            ->filter(fn ($field) => $action->isFieldClearable($field))
            ->isEmpty());
});

it('works with chunked selections for large tables', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->chunkSelectedRecords(2);
    $posts = collect(range(1, 7))->map(fn () => makePost());

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => ['status', 'published']]));

    expect(Post::query()->where('status', 'published')->count())->toBe(7)
        ->and(sentNotifications()[0]['body'])->toBe('7 records updated.');
});

it('speaks Bulgarian', function () {
    app()->setLocale('bg');
    $posts = collect([makePost(), makePost(['status' => 'review'])]);

    livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', $posts, data: bulkEditData([0 => ['status', 'review']]));

    expect(sentNotifications())->toContain([
        'title' => 'Промените са приложени',
        'body' => 'Обновен е 1 запис. 1 запис вече имаше тези стойности.',
        'status' => 'success',
    ]);
});

it('explains when mutateChangesUsing does not return an array', function () {
    PostResource::$configureBulkEdit = fn (BulkEditAction $action) => $action->mutateChangesUsing(fn () => 'oops');
    $post = makePost();

    expect(fn () => livewire(ListPosts::class)
        ->callTableBulkAction('bulkEdit', [$post], data: bulkEditData([0 => ['status', 'review']])))
        ->toThrow(LogicException::class, 'must return the array of changes');

    expect($post->fresh()->status)->toBe('draft');
});
