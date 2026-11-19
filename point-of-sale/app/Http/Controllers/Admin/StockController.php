<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StockInRequest;
use App\Http\Requests\Admin\StockOpnameRequest;
use App\Http\Requests\Admin\StockOutRequest;
use App\Services\Admin\AdminStockService;
use App\Support\Format;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        private readonly AdminStockService $stockService
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'ingredient_id' => ['nullable', 'integer'],
            'type'          => ['nullable', Rule::in(StockMovementType::values())],
            'date_from'     => ['nullable', 'date'],
            'date_to'       => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $data = $this->stockService->getStockOverview($filters, 25);

        return view('admin.stock.index', $data);
    }

    public function stockIn(StockInRequest $request): RedirectResponse
    {
        $ingredient = $this->stockService->recordStockIn(
            (int) $request->validated('ingredient_id'),
            (float) $request->validated('quantity'),
            $request->filled('unit_cost') ? (float) $request->validated('unit_cost') : null,
            $request->user(),
            $request->validated('note')
        );

        return back()->with('success', "Stok {$ingredient->name} bertambah.");
    }

    public function stockOut(StockOutRequest $request): RedirectResponse
    {
        try {
            $ingredient = $this->stockService->recordStockOut(
                (int) $request->validated('ingredient_id'),
                (float) $request->validated('quantity'),
                $request->user(),
                $request->validated('note')
            );
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', "Stok {$ingredient->name} berkurang.");
    }

    public function opname(StockOpnameRequest $request): RedirectResponse
    {
        $result = $this->stockService->recordOpname(
            (int) $request->validated('ingredient_id'),
            (float) $request->validated('counted_stock'),
            $request->user(),
            $request->validated('note')
        );

        $diff = $result['difference'];
        $name = $result['ingredient']->name;
        $unit = $result['ingredient']->unit;

        $msg = $diff == 0.0
            ? "Stok {$name} sesuai, tidak ada selisih."
            : "Opname {$name} dicatat (selisih " . ($diff > 0 ? '+' : '') . Format::qty($diff) . ' ' . $unit . ').';

        return back()->with('success', $msg);
    }
}
