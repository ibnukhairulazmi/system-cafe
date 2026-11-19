<?php

namespace App\Services\Admin;

use App\Enums\StockMovementType;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;

class AdminStockService
{
    public function __construct(
        private readonly StockService $stockService
    ) {}

    /**
     * Mengambil riwayat mutasi stok berpaginasi beserta data pendukung filter.
     */
    public function getStockOverview(array $filters, int $perPage = 25): array
    {
        $movements = StockMovement::query()
            ->with(['ingredient', 'user'])
            ->when($filters['ingredient_id'] ?? null, fn($q, $v) => $q->where('ingredient_id', $v))
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['date_from'] ?? null, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'movements'   => $movements,
            'filters'     => $filters,
            'ingredients' => Ingredient::orderBy('name')->get(['id', 'name', 'unit', 'stock', 'min_stock']),
            'types'       => StockMovementType::options(),
        ];
    }

    public function recordStockIn(int $ingredientId, float $quantity, ?float $unitCost, User $user, ?string $note): Ingredient
    {
        $ingredient = Ingredient::findOrFail($ingredientId);

        $this->stockService->stockIn($ingredient, $quantity, $unitCost, $user, $note);

        return $ingredient;
    }

    public function recordStockOut(int $ingredientId, float $quantity, User $user, ?string $note): Ingredient
    {
        $ingredient = Ingredient::findOrFail($ingredientId);

        $this->stockService->stockOut($ingredient, $quantity, $user, $note);

        return $ingredient;
    }

    public function recordOpname(int $ingredientId, float $countedStock, User $user, ?string $note): array
    {
        $ingredient = Ingredient::findOrFail($ingredientId);

        $movement = $this->stockService->opname($ingredient, $countedStock, $user, $note);

        return [
            'ingredient' => $ingredient,
            'difference' => (float) $movement->quantity,
        ];
    }
}
