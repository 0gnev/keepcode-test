<?php

namespace App\Providers;

use App\Faker\ToolProvider;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Generator::class, function () {
            $faker = Factory::create();
            $faker->addProvider(new ToolProvider($faker));

            return $faker;
        });
        $this->app->bind(Generator::class . ':' . config('app.faker_locale'), Generator::class);

    }

    public function boot(): void
    {
        //
    }
}
