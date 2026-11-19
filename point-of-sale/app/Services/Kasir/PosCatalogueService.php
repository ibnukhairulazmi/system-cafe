<?php

namespace App\Services\Kasir;

use App\Models\CafeTable;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Collection;

class PosCatalogueService
{
    /**
     * Mengambil konfigurasi lengkap untuk antarmuka POS kasir:
     * - Katalog produk aktif beserta stok porsi maksimum
     * - Kategori produk aktif
     * - Daftar meja aktif beserta status keterisian
     * - Konfigurasi pajak, diskon, & pembulatan
     */
    public function getCatalogueConfig(): array
    {
        $products = $this->getActiveProducts();

        return [
            'products'    => $products,
            'categories'  => $this->extractCategories($products),
            'taxEnabled'  => Setting::get('tax_enabled', '0') === '1',
            'taxRate'     => (float) Setting::get('tax_rate', 0),
            'discount'    => (float) Setting::get('discount', 0),
            'rounding'    => 500,
            'csrf'        => csrf_token(),
            'checkoutUrl' => route('kasir.checkout'),
            'tables'      => $this->getActiveTables(),
        ];
    }

    /**
     * Mengambil seluruh produk yang aktif beserta ketersediaan porsi berdasarkan stok bahan.
     */
    public function getActiveProducts(): Collection
    {
        return Product::active()
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->with(['category', 'ingredients'])
            ->orderBy('name')
            ->get()
            ->map(fn(Product $p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'price'       => (float) $p->price,
                'category_id' => $p->category_id,
                'category'    => $p->category?->name ?? 'Uncategorized',
                'max'         => $p->availablePortions(),
            ])
            ->values();
    }

    /**
     * Mengekstrak daftar kategori unik yang memiliki produk aktif.
     */
    public function extractCategories(Collection $products): Collection
    {
        return $products->unique('category_id')
            ->map(fn($p) => [
                'id'   => $p['category_id'],
                'name' => $p['category'],
            ])
            ->sortBy('name')
            ->values();
    }

    /**
     * Mengambil daftar meja cafe yang aktif.
     */
    public function getActiveTables(): Collection
    {
        return CafeTable::where('is_active', true)
            ->orderBy('area')
            ->orderBy('name')
            ->get()
            ->map(fn(CafeTable $t) => [
                'id'       => $t->id,
                'name'     => $t->name,
                'area'     => $t->area,
                'capacity' => $t->capacity,
                'occupied' => $t->is_occupied,
            ])
            ->values();
    }
}
