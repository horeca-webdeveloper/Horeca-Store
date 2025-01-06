<?php

namespace Botble\Ecommerce\AdsTracking;

use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\Product;
use Botble\SeoHelper\Facades\SeoHelper;

class FacebookPixel
{
    protected array $events = [];

    public function view(Product $product): static
    {
        $this->pushEvent('ViewContent', [$product], [
            'content_category' => $product->categories()->first()->name ?? '',
            'content_name' => $product->name,
            'content_type' => 'product',
            'contents' => [
                [
                    'id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'currency' => get_application_currency()->title,
            'value' => $product->price,
        ]);

        return $this;
    }

    public function checkout(array $items, float $value): static
    {
        $this->pushEvent('InitiateCheckout', $items, [
            'content_name' => 'Checkout',
            'contents' => array_map(fn ($item) => [
                'id' => $item->id,
                'quantity' => $item->cartItem->qty,
            ], $items),
            'currency' => get_application_currency()->title,
            'num_items' => count($items),
            'value' => $value,
        ]);

        return $this;
    }

    public function purchase(Order $order): static
    {
        $products = $order->getOrderProducts()->all();

        $this->pushEvent('Purchase', $products, [
            'content_name' => 'Purchase',
            'content_type' => 'product',
            'contents' => $order->products->map(fn ($item) => [
                'id' => $item->product_id,
                'quantity' => $item->qty,
            ])->values()->all(),
            'currency' => get_application_currency()->title,
            'value' => $order->sub_total,
        ]);

        return $this;
    }

    public function addToCart(Product $product, int $quantity, float $value): self
    {
        $this->pushEvent('AddToCart', [$product], [
            'content_name' => 'Add to Cart',
            'content_type' => 'product',
            'contents' => [
                [
                    'id' => $product->id,
                    'quantity' => $quantity,
                ],
            ],
            'currency' => get_application_currency()->title,
            'value' => $value,
        ]);

        return $this;
    }

    public function isEnabled(): bool
    {
        return get_ecommerce_setting('facebook_pixel_enabled', false) && get_ecommerce_setting('facebook_pixel_id');
    }

    public function pushEvent(string $event, array $items = [], array $data = []): void
    {
        $this->events[$event] = [
            ...$data,
            'content_ids' => array_map(fn ($item) => (string) $item->id, $items),
        ];
    }

    public function render(): string
    {
        if (empty($this->events)) {
            return '';
        }

        $content = '';

        foreach ($this->events as $event => $data) {
            $content .= "fbq('track', '$event', " . json_encode($data) . ');';
        }

        return <<<HTML
            <script>
                if (typeof fbq !== "undefined") {
                    $content
                }
            </script>
        HTML;
    }

    public function pushScriptsToFooter(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        add_filter(THEME_FRONT_FOOTER, function (?string $html) {
            return $html . view(EcommerceHelper::viewPath('includes.facebook-pixel-script'))->render() . $this->render();
        }, 999);

        add_filter('ecommerce_checkout_footer', function (?string $html) {
            return $html . SeoHelper::meta()->getAnalytics()->render() . $this->render();
        }, 999);
    }
}
