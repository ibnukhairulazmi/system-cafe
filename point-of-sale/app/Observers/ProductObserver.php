<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ActivityLogger;


class ProductObserver
{
    public function updated(Product $product): void
    {
        if ($product->wasChanged('price')) {
            ActivityLogger::log('product.price_changed', $product, [
                'name' => $product->name,
                'from' => $product->getOriginal('price'),
                'to' => $product->price,
            ]);
        }
    }
}
