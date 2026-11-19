<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'cafe_name'     => 'Ruang Teduh Coffee & Eatery',
            'cafe_tagline'  => 'Tempat Berteduh, Menikmati Kopi & Cerita',
            'cafe_address'  => "Jl. Kemang Raya No. 12B\nJakarta Selatan, DKI Jakarta 12730",
            'cafe_hours'    => "Senin - Jumat: 08:00 - 22:00\nSabtu - Minggu: 08:00 - 23:00",
            'cafe_phone'    => '021-78901234',
            'cafe_whatsapp' => '6281234567890',
            'cafe_maps_url' => 'https://maps.google.com',
            'cafe_instagram'=> '@ruangteduh.coffee',
            'tax_enabled'   => '1',
            'tax_rate'      => '11',
            'discount'      => '0',
            'rounding'      => '500', // Pembayaran dibulatkan ke kelipatan 500 / 1000
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
