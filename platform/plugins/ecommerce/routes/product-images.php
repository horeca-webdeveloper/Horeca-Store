<?php

use Botble\Base\Facades\AdminHelper;
use Botble\Ecommerce\Http\Controllers\ImportProductImageController;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function () {
    Route::group(
        [
            'namespace' => 'Botble\Ecommerce\Http\Controllers',
            'prefix' => 'ecommerce',
            'as' => 'ecommerce.',
        ],
        function () {
            Route::group([
                'prefix' => 'product-iamges',
                'as' => 'product-images.',
            ], function () {
                Route::match(['GET', 'POST'], '', [
                    'uses' => 'ImportProductImageController@index',
                    'as' => 'index',
                    // Permission check removed for public access
                ]);

                Route::put('{product}', [
                    'uses' => 'ImportProductImageController@update',
                    'as' => 'update',
                    // Permission check removed for public access
                ])->wherePrimaryKey();
            });
        }
    );

    Route::group(['prefix' => 'tools/data-synchronize/import/product-images', 'as' => 'ecommerce.product-images.import.'], function () {
        Route::get('/', [ImportProductImageController::class, 'index'])->name('index');
        Route::post('validate', [ImportProductImageController::class, 'validateData'])->name('validate');
        Route::post('import', [ImportProductImageController::class, 'import'])->name('store');
        Route::post('download-example', [ImportProductImageController::class, 'downloadExample'])->name('download-example');
    });

  
});

