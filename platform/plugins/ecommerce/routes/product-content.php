<?php

use Botble\Base\Facades\AdminHelper;
use Botble\Ecommerce\Http\Controllers\ProductContentController;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function () {
    Route::group(
        [
            'namespace' => 'Botble\Ecommerce\Http\Controllers',
            'prefix' => 'ecommerce',
            'as' => 'ecommerce.',
        ],
        function () {
            // Define a new route group for product-content
            Route::group([
                'prefix' => 'product-content',
                'as' => 'product-content.',
            ], function () {
                Route::match(['GET', 'POST'], '', [
                    'uses' => 'ProductContentController@index',
                    'as' => 'index',
                    'permission' => 'ecommerce.product-content.index', // Define the permission
                ]);

                Route::put('{product}', [
                    'uses' => 'ProductContentController@update',
                    'as' => 'update',
                    'permission' => 'ecommerce.product-content.edit', // Define the permission for editing content
                ])->wherePrimaryKey();
            });
        }
    );
});
