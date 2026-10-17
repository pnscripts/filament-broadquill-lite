<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** Set by tests to cancel saves for some titles. */
    public static ?string $cancelSaveForTitle = null;

    /** Set by tests to throw while saving some titles. */
    public static ?string $throwForTitle = null;

    /** @var array<int, string> */
    public static array $savedTitles = [];

    protected $fillable = [
        'title', 'slug', 'status', 'category_id', 'is_featured', 'is_locked', 'published_on',
        'price', 'notes', 'settings', 'settings->color', 'updated_by', 'labels', 'remind_at', 'opens_at', 'color',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_locked' => 'boolean',
        'published_on' => 'date',
        'price' => 'decimal:2',
        'settings' => 'array',
        'labels' => 'array',
        'remind_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            if (static::$cancelSaveForTitle !== null && $post->title === static::$cancelSaveForTitle) {
                return false;
            }

            if (static::$throwForTitle !== null && $post->title === static::$throwForTitle) {
                throw new \RuntimeException('Saving failed on purpose.');
            }

            return null;
        });

        static::saved(function (Post $post): void {
            static::$savedTitles[] = $post->title;
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
