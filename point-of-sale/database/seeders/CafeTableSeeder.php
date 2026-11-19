<?php

namespace Database\Seeders;

use App\Models\CafeTable;
use Illuminate\Database\Seeder;

class CafeTableSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            ['name' => 'Meja 01 (Indoor)', 'area' => 'Indoor', 'capacity' => 2, 'is_active' => true],
            ['name' => 'Meja 02 (Indoor)', 'area' => 'Indoor', 'capacity' => 2, 'is_active' => true],
            ['name' => 'Meja 03 (Indoor)', 'area' => 'Indoor', 'capacity' => 4, 'is_active' => true],
            ['name' => 'Meja 04 (Indoor)', 'area' => 'Indoor', 'capacity' => 4, 'is_active' => true],
            ['name' => 'Meja 05 (Indoor)', 'area' => 'Indoor', 'capacity' => 6, 'is_active' => true],

            ['name' => 'Meja 06 (Garden)', 'area' => 'Outdoor', 'capacity' => 2, 'is_active' => true],
            ['name' => 'Meja 07 (Garden)', 'area' => 'Outdoor', 'capacity' => 4, 'is_active' => true],
            ['name' => 'Meja 08 (Garden)', 'area' => 'Outdoor', 'capacity' => 4, 'is_active' => true],
            ['name' => 'Meja 09 (Garden)', 'area' => 'Outdoor', 'capacity' => 8, 'is_active' => true],

            ['name' => 'Meja 10 (VIP Lounge)', 'area' => 'VIP', 'capacity' => 12, 'is_active' => true],
        ];

        foreach ($tables as $t) {
            CafeTable::firstOrCreate(['name' => $t['name']], $t);
        }
    }
}

