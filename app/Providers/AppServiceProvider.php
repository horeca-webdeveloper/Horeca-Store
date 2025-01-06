<?php

namespace App\Providers;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
{
    Validator::extend('validateTime', function ($attribute, $value, $parameters, $validator) {
        // Custom validation logic for time
        return preg_match('/^[0-2][0-9]:[0-5][0-9]$/', $value); // Example for HH:mm format
    });
}
}
