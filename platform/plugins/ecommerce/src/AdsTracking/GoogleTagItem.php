<?php

namespace Botble\Ecommerce\AdsTracking;

class GoogleTagItem
{
    protected array $attributes = [];

    public function __construct(
        string $id,
        string $name,
        float $price,
        ?int $quantity = null,
        array $attributes = []
    ) {
        $this->attributes = [
            'item_id' => $id,
            'item_name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            ...$attributes,
        ];
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
