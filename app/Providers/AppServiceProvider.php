<?php

namespace App\Providers;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Filament::serving(function () {
            // Using Vite
            Filament::registerTheme(
                app(Vite::class)('resources/css/app.css'),
            );
        });

        /**
         * Defines the "Older than..." validation rule.
         */
        Validator::extend(
            rule: 'older_than',
            extension: fn ($attribute, $value, $parameters) => Carbon::now()
                ->diff(new Carbon($value))
                    ->y >= (int) $parameters[0],
        );

        Validator::replacer(
            rule: 'older_than',
            replacer: fn ($message, $attribute, $rule, $parameters) => str_replace(':value', $parameters[0], $message)
        );

        /**
         * Defines the "Younger than..." validation rule.
         */
        Validator::extend(
            rule: 'younger_than',
            extension: fn ($attribute, $value, $parameters) => Carbon::now()
                ->diff(new Carbon($value))
                    ->y < (int) $parameters[0],
        );

        Validator::replacer(
            rule: 'younger_than',
            replacer: fn ($message, $attribute, $rule, $parameters) => str_replace(':value', $parameters[0], $message)
        );
    }
}
