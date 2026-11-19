<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Superadmin\SuperadminAuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        private readonly SuperadminAuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $data = $this->auditService->getStockAudit($request->all(), 15, 20);

        return view('superadmin.stock.index', $data);
    }
}
