<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Requests\Admin\RecipeRequest;
use App\Models\Product;
use App\Services\Admin\AdminProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly AdminProductService $productService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q'           => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'status'      => ['nullable', 'in:active,inactive'],
        ]);

        $data = $this->productService->getPaginatedProducts($filters, 15);

        return view('admin.products.index', $data);
    }

    public function create(): View
    {
        return view('admin.products.form', $this->productService->getCreateFormData());
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->productService->createProduct(
            $request->validated(),
            $request->file('image'),
            $request->boolean('is_active', true),
            $request->boolean('show_on_web', true),
            (int) $request->input('sort_order', 0)
        );

        return redirect()->route('admin.products.recipe.edit', $product)
            ->with('success', 'Menu ditambahkan. Lanjutkan dengan mengatur resep agar stok berkurang otomatis.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', $this->productService->getEditFormData($product));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->updateProduct(
            $product,
            $request->validated(),
            $request->file('image'),
            $request->boolean('remove_image'),
            $request->boolean('is_active'),
            $request->boolean('show_on_web'),
            (int) $request->input('sort_order', 0)
        );

        return redirect()->route('admin.products.index')->with('success', 'Menu diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->deleteProduct($product);

        return back()->with('success', 'Menu dihapus.');
    }

    public function recipe(Product $product): View
    {
        return view('admin.products.recipe', $this->productService->getRecipeFormData($product));
    }

    public function updateRecipe(RecipeRequest $request, Product $product): RedirectResponse
    {
        $this->productService->updateRecipe(
            $product,
            $request->validated('ingredients') ?? []
        );

        return redirect()->route('admin.products.index')->with('success', "Resep {$product->name} disimpan.");
    }
}
