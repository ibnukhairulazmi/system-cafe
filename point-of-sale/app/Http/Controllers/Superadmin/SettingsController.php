<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Superadmin\UpdateSettingsRequest;
use App\Services\Superadmin\SuperadminSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SuperadminSettingsService $settingsService
    ) {}

    public function edit(): View
    {
        Gate::authorize('manage-settings');

        return view('superadmin.settings', [
            'settings' => $this->settingsService->getSettings(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $changes = $this->settingsService->updateSettings(
            $request->validated(),
            $request->boolean('tax_enabled')
        );

        return back()->with('success', ! empty($changes)
            ? 'Pengaturan disimpan. Berlaku untuk transaksi berikutnya.'
            : 'Tidak ada perubahan.');
    }
}
