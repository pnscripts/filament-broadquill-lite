<?php

use PNScripts\BroadquillLite\Tests\Fixtures\Models\Post;

/**
 * @param  array<string, mixed>  $attributes
 */
function makePost(array $attributes = []): Post
{
    static $n = 0;
    $n++;

    return Post::query()->create([
        'title' => "Post {$n}",
        'slug' => "post-{$n}-" . uniqid(),
        ...$attributes,
    ]);
}

/**
 * Builds the modal state for the ticked fields.
 *
 * @param  array<int, array{0: string, 1: mixed, 2?: string}>  $ticks  [index => [fieldName, value, mode]]
 * @return array<string, mixed>
 */
function bulkEditData(array $ticks): array
{
    $data = [];

    foreach ($ticks as $index => $tick) {
        $data['broadquill']['apply']["f{$index}"] = true;
        $data['broadquill']['mode']["f{$index}"] = $tick[2] ?? 'set';
        data_set($data, $tick[0], $tick[1]);
    }

    return $data;
}

/**
 * The notifications sent during the test, as [title, body, status] arrays (reads them once).
 *
 * @return array<int, array{title: ?string, body: ?string, status: ?string}>
 */
function sentNotifications(): array
{
    app(\Filament\Notifications\NotificationMailbox::class)->flush();
    $component = new \Filament\Notifications\Livewire\Notifications;
    $component->mount();
    $component->pullNotificationsFromSession();

    return $component->notifications
        ->map(fn (\Filament\Notifications\Notification $n): array => [
            'title' => $n->getTitle(),
            'body' => $n->getBody(),
            'status' => $n->getStatus(),
        ])
        ->values()
        ->all();
}
