<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use App\Services\Kasir\KasirTableService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TableController extends Controller
{
    public function __construct(
        private readonly KasirTableService $tableService
    ) {}

    public function index(): View
    {
        return view('kasir.tables.index', [
            'tables' => $this->tableService->getTablesOverview(),
        ]);
    }

    public function occupy(Request $request, CafeTable $table): RedirectResponse
    {
        try {
            $this->tableService->occupy(
                $table,
                $request->user()->id,
                $request->boolean('force')
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$table->name} ditandai terisi.");
    }

    public function release(CafeTable $table): RedirectResponse
    {
        $this->tableService->release($table);

        return back()->with('success', "{$table->name} kosong.");
    }
}
