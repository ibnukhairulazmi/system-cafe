<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\EmployeeFilterRequest;
use App\Http\Requests\Superadmin\StoreEmployeeRequest;
use App\Http\Requests\Superadmin\UpdateEmployeeRequest;
use App\Models\User;
use App\Services\Superadmin\SuperadminEmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly SuperadminEmployeeService $employeeService
    ) {}

    public function index(EmployeeFilterRequest $request): View
    {
        $employees = $this->employeeService->getPaginatedEmployees(
            $request->validated('q'),
            $request->validated('role'),
            $request->validated('status'),
            15
        );

        return view('superadmin.employees.index', [
            'employees' => $employees,
            'filters'   => $request->validated(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('superadmin.employees.create');
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->employeeService->createEmployee(
            $request->validated(),
            $request->boolean('is_active', true)
        );

        return redirect()->route('superadmin.employees.index')
            ->with('success', "Pegawai {$employee->name} berhasil ditambahkan.");
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('superadmin.employees.edit', ['employee' => $user]);
    }

    public function update(UpdateEmployeeRequest $request, User $user): RedirectResponse
    {
        $this->employeeService->updateEmployee($user, $request->validated());

        return redirect()->route('superadmin.employees.index')
            ->with('success', "Data {$user->name} berhasil diperbarui.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        Gate::authorize('toggleActive', $user);

        $isActive = $this->employeeService->toggleActive($user);

        return back()->with('success', $isActive
            ? "{$user->name} diaktifkan kembali."
            : "{$user->name} dinonaktifkan. Riwayat transaksinya tetap tersimpan.");
    }
}
