<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\Superadmin\SuperadminAuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function __construct(
        private readonly SuperadminAuditService $auditService
    ) {}

    public function index(Request $request): View
    {
        $data = $this->auditService->getShifts($request->all(), 15);

        return view('superadmin.shifts.index', $data);
    }

    public function show(Shift $shift): View
    {
        $shift->load(['user', 'transactions']);

        return view('superadmin.shifts.show', compact('shift'));
    }
}
