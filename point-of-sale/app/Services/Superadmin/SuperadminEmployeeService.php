<?php

namespace App\Services\Superadmin;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SuperadminEmployeeService
{
    /**
     * Mengambil daftar pegawai (Admin & Kasir) berpaginasi beserta filter kata kunci, role, dan status.
     */
    public function getPaginatedEmployees(?string $search, ?string $role, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->when($search, function ($query, $q) {
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $query->where(fn($w) => $w->whereLike('name', $like)->orWhereLike('email', $like));
            })
            ->when($role, fn($query, $r) => $query->where('role', $r))
            ->when($status, fn($query, $s) => $query->where('is_active', $s === 'active'))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Menambahkan akun pegawai baru beserta pencatatan ke log aktivitas.
     */
    public function createEmployee(array $data, bool $isActive = true): User
    {
        $data['is_active'] = $isActive;

        $employee = User::create($data);

        ActivityLogger::log('employee.created', $employee, ['role' => $employee->role->value]);

        return $employee;
    }

    /**
     * Memperbarui profil/kredensial pegawai beserta pelacakan perubahan role.
     */
    public function updateEmployee(User $user, array $data): User
    {
        $oldRole = $user->role->value;

        $user->fill($data)->save();

        ActivityLogger::log('employee.updated', $user, [
            'changed' => array_keys($user->getChanges()),
        ]);

        if ($user->role->value !== $oldRole) {
            ActivityLogger::log('employee.role_changed', $user, [
                'from' => $oldRole,
                'to'   => $user->role->value,
            ]);
        }

        return $user;
    }

    /**
     * Mengaktifkan atau menonaktifkan akun pegawai.
     * Jika dinonaktifkan, seluruh sesi login yang sedang aktif dipaksa keluar.
     */
    public function toggleActive(User $user): bool
    {
        $user->update(['is_active' => ! $user->is_active]);

        if (! $user->is_active) {
            $this->revokeSessions($user);
        }

        ActivityLogger::log($user->is_active ? 'employee.activated' : 'employee.deactivated', $user);

        return $user->is_active;
    }

    /**
     * Menghapus sesi login pegawai dari tabel sessions jika menggunakan driver database.
     */
    public function revokeSessions(User $user): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }
    }
}
