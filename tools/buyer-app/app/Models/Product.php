<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = ['name', 'sku', 'status', 'category_id', 'is_featured', 'price', 'stock', 'launch_date', 'notes'];

    protected $casts = [
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'launch_date' => 'date',
    ];


    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
