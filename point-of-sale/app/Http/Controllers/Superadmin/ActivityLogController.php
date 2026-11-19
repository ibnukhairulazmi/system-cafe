<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\ActivityLogFilterRequest;
use App\Services\Superadmin\SuperadminAuditService;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly SuperadminAuditService $auditService
    ) {}

    public function index(ActivityLogFilterRequest $request): View
    {
        $data = $this->auditService->getActivityLogs($request->validated(), 30);

        return view('superadmin.activity-logs.index', $data);
    }
}
