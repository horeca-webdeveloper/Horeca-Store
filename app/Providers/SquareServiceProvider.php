<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Square\SquareClient;

class SquareServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(SquareClient::class, function ($app) {
            return new SquareClient([
                'accessToken' => env('SQUARE_ACCESS_TOKEN'),
                'environment' => 'production', // or 'sandbox' for testing
            ]);
        });
    }

    public function boot()
    {
        //
    }
}
