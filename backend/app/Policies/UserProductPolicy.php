<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserProduct;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserProductPolicy
{
    use HandlesAuthorization;

    public function view(User $user, UserProduct $userProduct): bool
    {
        return $user->id === $userProduct->user_id;
    }

    public function renew(User $user, UserProduct $userProduct): bool
    {
        return $user->id === $userProduct->user_id && $userProduct->ownership_type === 'rent';
    }

    public function purchase(User $user, UserProduct $userProduct): bool
    {
        return $user->id === $userProduct->user_id;
    }
}
