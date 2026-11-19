<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name'           => 'CV Java Agro Perkasa',
                'contact_person' => 'Budi Santoso',
                'phone'          => '081122334455',
                'email'          => 'order@javaagro.co.id',
                'address'        => 'Kawasan Industri Sentul Kav. 14, Bogor, Jawa Barat',
            ],
            [
                'name'           => 'PT Prima Dairy Mandiri',
                'contact_person' => 'Dewi Anggraini',
                'phone'          => '081233445566',
                'email'          => 'sales@primadairy.com',
                'address'        => 'Jl. Rawa Gelam II No. 8, Kawasan Industri Pulogadung, Jakarta Timur',
            ],
            [
                'name'           => 'Toko Sumber Bahan Kue & Sirup',
                'contact_person' => 'Hendra Wijaya',
                'phone'          => '081344556677',
                'email'          => 'sumberbahan@gmail.com',
                'address'        => 'Jl. Fatmawati Raya No. 45, Cilandak, Jakarta Selatan',
            ],
            [
                'name'           => 'Mitra Unggas & Daging Nusantara',
                'contact_person' => 'Rahmat Hidayat',
                'phone'          => '081455667788',
                'email'          => 'supply@mitradaging.co.id',
                'address'        => 'Jl. Pasar Minggu Raya No. 102, Jakarta Selatan',
            ],
            [
                'name'           => 'Berkah Sayur & Buah Segar',
                'contact_person' => 'Siti Nurhaliza',
                'phone'          => '081566778899',
                'email'          => 'berkahsegar@gmail.com',
                'address'        => 'Pasar Induk Kramat Jati Blok B No. 12, Jakarta Timur',
            ],
        ];

        foreach ($suppliers as $s) {
            Supplier::updateOrCreate(['name' => $s['name']], $s);
        }
    }
}
