<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Botble\Ecommerce\Repositories\Interfaces\ProductInterface;

class ProductContentController extends BaseController
{
    protected $productRepository;

    public function __construct(ProductInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    // Method for displaying the product content edit form
    public function index(Request $request)
    {
        $products = $this->productRepository->all(); // Fetch all products, or modify as needed

        return view('plugins/ecommerce::products.content', compact('products')); // Load the Blade template for editing product content
    }

    // Method for updating the product content
    public function update($id, Request $request)
    {
        $product = $this->productRepository->findOrFail($id);

        // Perform update logic here (e.g., saving content fields)
        $product->update($request->all());

        return redirect()->back()->with('success', 'Product content updated successfully');
    }
}
