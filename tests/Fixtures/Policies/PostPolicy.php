<?php

namespace PNScripts\BroadquillLite\Tests\Fixtures\Policies;

use Illuminate\Auth\Access\Response;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\Post;
use PNScripts\BroadquillLite\Tests\Fixtures\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Post $post): bool | Response
    {
        return ! $post->is_locked;
    }

    public function delete(User $user, Post $post): bool
    {
        return true;
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }
}
