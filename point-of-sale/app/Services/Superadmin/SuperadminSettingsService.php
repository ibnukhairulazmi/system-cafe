<?php

namespace App\Services\Superadmin;

use App\Models\Setting;
use App\Services\ActivityLogger;

class SuperadminSettingsService
{
    /**
     * Mengambil konfigurasi umum cafe (PPN, diskon tetap, identitas struk).
     */
    public function getSettings(): array
    {
        return [
            'cafe_name'    => Setting::get('cafe_name', ''),
            'cafe_address' => Setting::get('cafe_address', ''),
            'tax_enabled'  => Setting::get('tax_enabled', '0') === '1',
            'tax_rate'     => (float) Setting::get('tax_rate', 0),
            'discount'     => (float) Setting::get('discount', 0),
        ];
    }

    /**
     * Memperbarui konfigurasi cafe, melacak perubahan field, dan mencatat log aktivitas.
     */
    public function updateSettings(array $data, bool $taxEnabled): array
    {
        $new = [
            'cafe_name'    => $data['cafe_name'],
            'cafe_address' => $data['cafe_address'] ?? '',
            'tax_enabled'  => $taxEnabled ? '1' : '0',
            'tax_rate'     => (string) (float) $data['tax_rate'],
            'discount'     => (string) (float) $data['discount'],
        ];

        $changes = [];
        foreach ($new as $key => $value) {
            $old = (string) Setting::get($key, '');
            if ($old !== $value) {
                $changes[$key] = ['from' => $old, 'to' => $value];
                Setting::set($key, $value);
            }
        }

        if (! empty($changes)) {
            ActivityLogger::log('settings.updated', null, $changes);
        }

        return $changes;
    }
}
