<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Support\ApprovalState;
use Illuminate\Support\Facades\Cache;

trait ProductBrowseHelper
{
    protected function resolveBrowseProductDefaultUnit(object $product): string
    {
        $defaultKey = trim((string) ($product->fsatuandefault ?? ''));

        return match ($defaultKey) {
            '1' => trim((string) ($product->fsatuankecil ?? '')),
            '2' => trim((string) ($product->fsatuanbesar ?? '')),
            '3' => trim((string) ($product->fsatuanbesar2 ?? '')),
            default => trim((string) ($product->fsatuankecil ?? ''))
                ?: trim((string) ($product->fsatuanbesar ?? ''))
                ?: trim((string) ($product->fsatuanbesar2 ?? '')),
        };
    }

    protected function browseProducts()
    {
        return Cache::store('file')->remember('tr_poh:browse_products', now()->addMinutes(10), function () {
            return Product::select(
                'fprdid',
                'fprdcode',
                'fprdname',
                'fsatuandefault',
                'fsatuankecil',
                'fsatuanbesar',
                'fsatuanbesar2',
                'fqtykecil',
                'fqtykecil2',
                'fminstock'
            )
                ->whereRaw(ApprovalState::approvedSql('msprd.'))
                ->orderBy('fprdname')
                ->get();
        });
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
                    'code' => $product->fprdcode ?? '',
                    'default_unit' => $this->resolveBrowseProductDefaultUnit($product),
                    'units' => array_values(array_filter([
                        $product->fsatuankecil ?? '',
                        $product->fsatuanbesar ?? '',
                        $product->fsatuanbesar2 ?? '',
                    ])),
                    'stock' => $product->fminstock ?? 0,
                    'fsatuankecil' => $product->fsatuankecil ?? '',
                    'fsatuanbesar' => $product->fsatuanbesar ?? '',
                    'fsatuanbesar2' => $product->fsatuanbesar2 ?? '',
                    'fqtykecil' => (float) ($product->fqtykecil ?? 0),
                    'fqtykecil2' => (float) ($product->fqtykecil2 ?? 0),
                    'unit_ratios' => [
                        'satuankecil' => 1,
                        'satuanbesar' => (float) ($product->fqtykecil ?? 1),
                        'satuanbesar2' => (float) ($product->fqtykecil2 ?? 1),
                    ],
                ],
            ];
        })->toArray();
    }

    protected function browseProductMapCached(string $keyField = 'fprdcode'): array
    {
        $cacheKey = "tr_poh:product_map:{$keyField}";

        return Cache::store('file')->remember($cacheKey, now()->addMinutes(10), function () use ($keyField) {
            return $this->browseProductMap($this->browseProducts(), $keyField);
        });
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
