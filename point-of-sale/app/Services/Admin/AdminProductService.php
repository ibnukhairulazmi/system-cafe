<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Services\ActivityLogger;
use App\Support\Format;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AdminProductService
{
    /**
     * Disk penyimpanan gambar produk (public di lokal, supabase di Vercel).
     */
    private function imageDisk(): string
    {
        return config('filesystems.product_disk', 'public');
    }

    /**
     * Mengambil daftar produk berpaginasi beserta filter dan pilihan kategori.
     */
    public function getPaginatedProducts(array $filters, int $perPage = 15): array
    {
        $products = Product::query()
            ->with('category')
            ->withCount('ingredients')
            ->when($filters['q'] ?? null, fn($q, $v) => $q->where(
                fn($w) => $w->whereLike('name', Format::like($v))
                    ->orWhereLike('sku', Format::like($v))
            ))
            ->when($filters['category_id'] ?? null, fn($q, $v) => $q->where('category_id', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('is_active', $v === 'active'))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'products'   => $products,
            'filters'    => $filters,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
        ];
    }

    public function getCreateFormData(): array
    {
        return [
            'categories' => Category::active()->orderBy('name')->pluck('name', 'id'),
        ];
    }

    public function getEditFormData(Product $product): array
    {
        return [
            'product'    => $product,
            'categories' => Category::active()
                ->orWhere('id', $product->category_id)
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }

    /**
     * Membuat produk baru beserta penanganan upload gambar dan audit log.
     */
    public function createProduct(array $data, ?UploadedFile $image, bool $isActive = true, bool $showOnWeb = true, int $sortOrder = 0): Product
    {
        $payload = $this->preparePayload($data, $image, false, $isActive, $showOnWeb, $sortOrder);

        $product = Product::create($payload);

        ActivityLogger::log('product.created', $product, ['name' => $product->name, 'price' => $product->price]);
        Cache::forget('public.menu');

        return $product;
    }

    /**
     * Memperbarui data produk beserta penggantian/penghapusan gambar jika ada.
     */
    public function updateProduct(Product $product, array $data, ?UploadedFile $image, bool $removeImage, bool $isActive, bool $showOnWeb, int $sortOrder): Product
    {
        $oldImage = $product->image_path;

        $payload = $this->preparePayload($data, $image, $removeImage, $isActive, $showOnWeb, $sortOrder);
        $product->update($payload);

        $this->deleteImageIfUnused($oldImage, $product);
        Cache::forget('public.menu');

        return $product;
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
        ActivityLogger::log('product.deleted', $product, ['name' => $product->name]);
        Cache::forget('public.menu');
    }

    /**
     * Menyiapkan data form resep untuk suatu produk.
     */
    public function getRecipeFormData(Product $product): array
    {
        $product->load('ingredients');

        $rows = old('ingredients') ?? $product->ingredients
            ->map(fn($i) => [
                'ingredient_id' => (string) $i->id,
                'quantity'      => (float) $i->pivot->quantity,
            ])
            ->values()
            ->all();

        $ingredients = Ingredient::orderBy('name')->get(['id', 'name', 'unit']);

        return [
            'product'       => $product,
            'rows'          => $rows,
            'ingredients'   => $ingredients,
            'ingredientMap' => $ingredients->mapWithKeys(fn($i) => [(string) $i->id => ['unit' => $i->unit]]),
        ];
    }

    /**
     * Menyinkronkan bahan baku resep untuk produk.
     */
    public function updateRecipe(Product $product, array $ingredients): void
    {
        $sync = collect($ingredients)
            ->mapWithKeys(fn($row) => [(int) $row['ingredient_id'] => ['quantity' => $row['quantity']]])
            ->all();

        $product->ingredients()->sync($sync);

        ActivityLogger::log('product.recipe_updated', $product, [
            'name'        => $product->name,
            'ingredients' => count($sync),
        ]);
    }

    private function preparePayload(array $data, ?UploadedFile $image, bool $removeImage, bool $isActive, bool $showOnWeb, int $sortOrder): array
    {
        unset($data['image'], $data['remove_image']);

        $data['is_active']   = $isActive;
        $data['show_on_web'] = $showOnWeb;
        $data['sort_order']  = $sortOrder;

        if ($image) {
            $data['image_path'] = $image->store('menu', $this->imageDisk());
        } elseif ($removeImage) {
            $data['image_path'] = null;
        }

        return $data;
    }

    private function deleteImageIfUnused(?string $path, Product $product): void
    {
        if (! $path || $path === $product->image_path) {
            return;
        }

        $stillUsed = Product::withTrashed()
            ->where('image_path', $path)
            ->where('id', '!=', $product->id)
            ->exists();

        if (! $stillUsed) {
            Storage::disk($this->imageDisk())->delete($path);
        }
    }
}
