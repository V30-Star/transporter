<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;

trait ProductBrowseHelper
{
    protected function browseProducts()
    {
        return Product::select(
            'fprdid',
            'fprdcode',
            'fprdname',
            'fsatuankecil',
            'fsatuanbesar',
            'fsatuanbesar2',
            'fqtykecil',
            'fqtykecil2',
            'fminstock'
        )
            ->orderBy('fprdname')
            ->get();
    }

    protected function browseProductMap($products, string $keyField = 'fprdcode'): array
    {
        return $products->mapWithKeys(function ($product) use ($keyField) {
            $key = $keyField === 'fprdid'
                ? (int) ($product->{$keyField} ?? 0)
                : trim((string) ($product->{$keyField} ?? ''));

            return [
                $key => [
                    'id' => $product->fprdid ?? null,
                    'name' => $product->fprdname ?? '',
                    'units' => array_values(array_filter([
                        $product->fsatuankecil ?? '',
                        $product->fsatuanbesar ?? '',
                        $product->fsatuanbesar2 ?? '',
                    ])),
                    'stock' => $product->fminstock ?? 0,
                ],
            ];
        })->toArray();
    }

    protected function browseProductData(string $keyField = 'fprdcode'): array
    {
        $products = $this->browseProducts();

        return [
            'products' => $products,
            'productMap' => $this->browseProductMap($products, $keyField),
        ];
    }
}
