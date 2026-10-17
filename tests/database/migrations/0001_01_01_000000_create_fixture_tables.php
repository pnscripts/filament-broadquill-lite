<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(true);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->foreignId('category_id')->nullable()->constrained();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->date('published_on')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('settings')->nullable();
            $table->string('secret')->nullable();
            $table->string('updated_by')->nullable();
            $table->json('labels')->nullable();
            $table->dateTime('remind_at')->nullable();
            $table->time('opens_at')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });
    }
};
