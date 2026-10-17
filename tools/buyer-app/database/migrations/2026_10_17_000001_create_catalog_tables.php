<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('status')->default('draft');
            $table->foreignId('category_id')->nullable()->constrained();
            $table->boolean('is_featured')->default(false);
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->date('launch_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
};
