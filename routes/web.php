<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
use Botble\Ecommerce\Http\Controllers\TempProductStatusController;
use Botble\Ecommerce\Http\Controllers\CategoryProductTypeController;

use Botble\Ecommerce\Http\Controllers\TempContentController;
use Botble\Ecommerce\Http\Controllers\ProductController;
use Botble\Ecommerce\Http\Controllers\DocumentController;
use Botble\Base\Facades\AdminHelper;
use Botble\Ecommerce\Http\Controllers\ImportProductImageController; // Ensure this path is correct
use Illuminate\Support\Facades\Route;
use Botble\Ecommerce\Http\Controllers\SpecificationController;
use Botble\Ecommerce\Http\Controllers\ImportProductDescriptionController;
use Botble\Ecommerce\Http\Controllers\EliteShipmentController;
use Botble\Ecommerce\Http\Controllers\ProductDocumentController;
use Botble\Ecommerce\Http\Controllers\ProductApprovalController;

// Route::middleware(['guest'])->group(function () {
	/* Temp Product Routes*/
	Route::get('admin/product-approval', [ProductApprovalController::class, 'index'])->name('product_approval.index');
	Route::post('admin/product-approval/pricing-approve', [ProductApprovalController::class, 'approvePricingChanges'])->name('product_approval.admin_pricing_approve');
	Route::post('admin/product-approval/graphics-approve', [ProductApprovalController::class, 'approveGraphicsChanges'])->name('product_approval.admin_graphics_approve');
	Route::get('admin/product-approval/{id}/edit-content-approval', [ProductApprovalController::class, 'editContentApproval'])->name('product_approval.edit_content');
	Route::post('admin/product-approval/{id}/comments', [ProductApprovalController::class, 'storeComment']);
	Route::put('admin/product-approval/{id}', [ProductApprovalController::class, 'approveContentChanges'])->name('product_approval.admin_content_approve');
	/*************************************/



	Route::get('ecommerce/temp-products-status', [TempProductStatusController::class, 'index'])->name('ecommerce/temp-products-status.index');
	Route::post('ecommerce/temp-products-status/update-pricing-changes', [TempProductStatusController::class, 'updatePricingChanges'])->name('temp-products.pricing_update');
	Route::post('ecommerce/temp-products-status/update-graphics-changes', [TempProductStatusController::class, 'updateGraphicsChanges'])->name('temp-products.graphics_update');
	Route::post('ecommerce/temp-products-status/update-content-changes', [TempProductStatusController::class, 'updateContentChanges'])->name('temp-products.content_update');
	Route::post('ecommerce/temp-products-status/approve', [TempProductStatusController::class, 'approveChanges'])->name('temp-products.approve');

	Route::get('admin/ecommerce/category-product-filter', [CategoryProductTypeController::class, 'index'])->name('categoryFilter.index');
	Route::get('admin/ecommerce/category-product-filter/{id}/edit', [CategoryProductTypeController::class, 'edit'])->name('categoryFilter.edit');
	Route::put('admin/ecommerce/category-product-filter/{id}', [CategoryProductTypeController::class, 'update'])->name('categoryFilter.update');
// });

	// Define route for showing the upload form
	Route::get('admin/ecommerce/upload-documents', [ProductDocumentController::class, 'showUploadForm'])->name('product-documents.form');

	// Define route for handling the form submission
	Route::post('admin/ecommerce/upload-documents', [ProductDocumentController::class, 'uploadDocuments'])->name('product-documents.upload');


	Route::get('/upload-form', function () {
		return view('upload-documents');
	});

	AdminHelper::registerRoutes(function () {
		Route::group(['namespace' => 'Botble\ProductImages\Http\Controllers', 'prefix' => 'ecommerce'], function () {
			Route::group(['prefix' => 'product-images', 'as' => 'product-images.'], function () {
				Route::get('/import', [ImportProductImageController::class, 'index'])->name('import.index');
				Route::post('/import', [ImportProductImageController::class, 'store'])->name('import.store');
			});
		});
	});
	Route::post('product-images/import/validate', [ImportProductImageController::class, 'validateImport'])->name('product-images.import.validate');
	Route::post('product-images/import/store', [ImportProductImageController::class, 'storeImport'])->name('product-images.import.store');

	// Route::get('/import', [ImportProductImageController::class, 'index'])->name('import.index');
	// Route::post('/import', [ImportProductImageController::class, 'store'])->name('import.store');
	Route::group(['namespace' => 'Botble\ProductImages\Http\Controllers', 'prefix' => 'ecommerce'], function () {
		Route::group(['prefix' => 'product-images', 'as' => 'product-images.'], function () {
			Route::get('/import', [ImportProductImageController::class, 'index'])->name('import.index');
			Route::post('/import', [ImportProductImageController::class, 'store'])->name('import.store');
		});
	});

	Route::get('specifications/upload', [SpecificationController::class, 'showUploadForm'])->name('specifications.upload.form');
	Route::post('specifications/upload', [SpecificationController::class, 'upload'])->name('specifications.upload');
	// Route::group(['middleware' => ['auth']], function () {
	//     Route::get('specifications/upload', [SpecificationController::class, 'showUploadForm'])->name('specifications.upload.form');
	//     Route::post('specifications/upload', [SpecificationController::class, 'upload'])->name('specifications.upload');
	// });


	Route::group(['namespace' => 'YourNamespace'], function () {
		Route::get('/products/search-sku', [ProductController::class, 'searchBySku'])->name('products.search-sku');
	});

	Route::get('/products/search-sku', [ProductController::class, 'searchBySku'])
	->name('products.search-sku');





	// Define the route for the create form
	Route::get('admin/ecommerce/create-shipment', [EliteShipmentController::class, 'create'])->name('eliteshipment.create');

	// Define the route to handle form submission
	Route::post('admin/ecommerce/store-shipment', [EliteShipmentController::class, 'store'])->name('eliteshipment.store');

