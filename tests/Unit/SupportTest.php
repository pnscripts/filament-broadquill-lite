<?php

use PNScripts\BroadquillLite\Support\AttributePath;
use PNScripts\BroadquillLite\Support\ColumnInspector;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\Post;

it('maps field names to Eloquent attributes', function () {
    expect(AttributePath::attribute('status'))->toBe('status')
        ->and(AttributePath::attribute('settings.color'))->toBe('settings->color')
        ->and(AttributePath::attribute('settings.theme.color'))->toBe('settings->theme->color')
        ->and(AttributePath::column('settings.color'))->toBe('settings')
        ->and(AttributePath::isJsonPath('settings.color'))->toBeTrue()
        ->and(AttributePath::isJsonPath('status'))->toBeFalse();
});

it('reads column nullability from the database', function () {
    $post = new Post;

    expect(ColumnInspector::isNullable($post, 'notes'))->toBeTrue()
        ->and(ColumnInspector::isNullable($post, 'category_id'))->toBeTrue()
        ->and(ColumnInspector::isNullable($post, 'status'))->toBeFalse()
        ->and(ColumnInspector::isNullable($post, 'title'))->toBeFalse()
        ->and(ColumnInspector::isNullable($post, 'no_such_column'))->toBeNull();
});
