<?php

namespace App\Providers;

use App\Models\UserProduct;
use App\Policies\UserProductPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        UserProduct::class => UserProductPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        //
    }
}
