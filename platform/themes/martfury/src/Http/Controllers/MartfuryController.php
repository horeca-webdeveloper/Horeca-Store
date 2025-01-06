<?php

namespace Theme\Martfury\Http\Controllers;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Repositories\Interfaces\ProductCategoryInterface;
use Botble\Ecommerce\Repositories\Interfaces\ProductInterface;
use Botble\Ecommerce\Services\Products\GetProductService;
use Botble\Newsletter\Forms\Fronts\NewsletterForm;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Http\Controllers\PublicController;
use Illuminate\Http\Request;
use Theme\Martfury\Http\Requests\SendDownloadAppLinksRequest;

class MartfuryController extends PublicController
{
    public function __construct(protected BaseHttpResponse $httpResponse)
    {
        $this->middleware(function ($request, $next) {
            if (! $request->ajax()) {
                return $this->httpResponse->setNextUrl(route('public.index'));
            }

            return $next($request);
        })->only([
            'ajaxGetProducts',
            'ajaxCart',
            'ajaxSearchProducts',
            'ajaxSendDownloadAppLinks',
            'ajaxGetProductsByCollection',
            'ajaxGetProductsByCategory',
        ]);
    }

    public function ajaxGetProducts(Request $request)
    {
        $products = get_products_by_collections([
            'collections' => [
                'by' => 'id',
                'value_in' => [$request->input('collection_id')],
            ],
            'take' => $request->integer('limit', 10) ?: 10,
            'with' => [
                'slugable',
                'variations',
                'productCollections',
                'variationAttributeSwatchesForProductList',
            ],
        ] + EcommerceHelper::withReviewsParams());

        $data = [];
        foreach ($products as $product) {
            $data[] = Theme::partial('product-item', compact('product'));
        }

        return $this->httpResponse->setData($data);
    }

    public function ajaxCart()
    {
        return $this->httpResponse->setData([
            'count' => Cart::instance('cart')->count(),
            'html' => Theme::partial('cart'),
        ]);
    }

    public function ajaxSearchProducts(Request $request, GetProductService $productService)
    {
        $request->merge(['num' => 10]);

        $with = [
            'slugable',
            'variations',
            'productCollections',
            'variationAttributeSwatchesForProductList',
        ];

        $products = $productService->getProduct($request, null, null, $with);

        $query = BaseHelper::stringify($request->input('q'));

        return $this->httpResponse->setData(Theme::partial('ajax-search-results', compact('products', 'query')));
    }

    public function ajaxSendDownloadAppLinks(SendDownloadAppLinksRequest $request)
    {
        do_action('form_extra_fields_validate', $request, NewsletterForm::class);

        EmailHandler::setModule(Theme::getThemeName())
            ->sendUsingTemplate('download_app', $request->input('email'), [], false, 'themes', __('Download apps'));

        return $this->httpResponse->setMessage(__('We sent an email with download links to your email, please check it!'));
    }

    public function ajaxGetProductsByCollection(int|string $id, Request $request)
    {
        $products = get_products_by_collections(array_merge([
            'collections' => [
                'by' => 'id',
                'value_in' => [$id],
            ],
            'take' => $request->integer('limit') ?: 10,
            'with' => EcommerceHelper::withProductEagerLoadingRelations(),
        ], EcommerceHelper::withReviewsParams()));

        $data = [];
        foreach ($products as $product) {
            $data[] = '<div class="ps-product">' . Theme::partial('product-item', compact('product')) . '</div>';
        }

        return $this->httpResponse->setData($data);
    }

    public function ajaxGetProductsByCategory(
        int|string $id,
        Request $request,
        ProductInterface $productRepository,
        ProductCategoryInterface $productCategoryRepository
    ) {
        if (! $request->expectsJson()) {
            return $this->httpResponse->setNextUrl(route('public.index'));
        }

        $category = $productCategoryRepository->getFirstBy(
            [
                'status' => BaseStatusEnum::PUBLISHED,
                'id' => $id,
            ],
            ['*'],
            [
                'activeChildren' => function ($query) {
                    return $query->limit(3);
                },
            ]
        );

        if (! $category) {
            return $this->httpResponse->setData([]);
        }

        $products = $productRepository->getProductsByCategories(array_merge([
            'categories' => [
                'by' => 'id',
                'value_in' => array_merge([$category->id], $category->activeChildren->pluck('id')->all()),
            ],
            'take' => $request->integer('limit') ?: 10,
        ], EcommerceHelper::withReviewsParams()));

        $data = [];
        foreach ($products as $product) {
            $data[] = '<div class="ps-product">' . Theme::partial('product-item', compact('product')) . '</div>';
        }

        return $this->httpResponse->setData($data);
    }
}
