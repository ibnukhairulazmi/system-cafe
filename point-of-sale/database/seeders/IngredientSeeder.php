<?php

namespace Database\Seeders;

use App\Enums\StockMovementType;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        $items = [
            ['name' => 'Biji Kopi Arabika House Blend', 'unit' => 'gram', 'min_stock' => 5000,  'last_cost' => 250,  'stock' => 50000],
            ['name' => 'Biji Kopi Robusta',            'unit' => 'gram', 'min_stock' => 3000,  'last_cost' => 150,  'stock' => 30000],
            ['name' => 'Susu UHT Full Cream',          'unit' => 'ml',   'min_stock' => 10000, 'last_cost' => 22,   'stock' => 80000],
            ['name' => 'Susu Evaporasi',               'unit' => 'ml',   'min_stock' => 3000,  'last_cost' => 28,   'stock' => 25000],
            ['name' => 'Gula Aren Cair',               'unit' => 'ml',   'min_stock' => 5000,  'last_cost' => 35,   'stock' => 30000],
            ['name' => 'Sirup Vanilla',                'unit' => 'ml',   'min_stock' => 2000,  'last_cost' => 120,  'stock' => 15000],
            ['name' => 'Bubuk Matcha Uji',             'unit' => 'gram', 'min_stock' => 1000,  'last_cost' => 450,  'stock' => 10000],
            ['name' => 'Bubuk Cokelat Premium',        'unit' => 'gram', 'min_stock' => 2000,  'last_cost' => 250,  'stock' => 20000],
            ['name' => 'Bubuk Red Velvet',             'unit' => 'gram', 'min_stock' => 1000,  'last_cost' => 220,  'stock' => 10000],
            ['name' => 'Bubuk Taro',                   'unit' => 'gram', 'min_stock' => 1000,  'last_cost' => 200,  'stock' => 10000],
            ['name' => 'Buah Strawberry Segar',        'unit' => 'gram', 'min_stock' => 2000,  'last_cost' => 65,   'stock' => 15000],
            ['name' => 'Daun Teh Hitam',               'unit' => 'gram', 'min_stock' => 2000,  'last_cost' => 120,  'stock' => 15000],
            ['name' => 'Daun Teh Hijau',               'unit' => 'gram', 'min_stock' => 2000,  'last_cost' => 150,  'stock' => 15000],
            ['name' => 'Sirup Leci',                   'unit' => 'ml',   'min_stock' => 2000,  'last_cost' => 95,   'stock' => 15000],
            ['name' => 'Lemon Segar',                  'unit' => 'gram', 'min_stock' => 2000,  'last_cost' => 35,   'stock' => 20000],
            ['name' => 'Pisang Cavendish',             'unit' => 'gram', 'min_stock' => 2000,  'last_cost' => 25,   'stock' => 15000],
            ['name' => 'Tepung Terigu Protein Tinggi', 'unit' => 'gram', 'min_stock' => 10000, 'last_cost' => 16,   'stock' => 60000],
            ['name' => 'Mentega / Butter Anchor',      'unit' => 'gram', 'min_stock' => 5000,  'last_cost' => 180,  'stock' => 35000],
            ['name' => 'Cokelat Batang Callebaut',     'unit' => 'gram', 'min_stock' => 2000,  'last_cost' => 220,  'stock' => 15000],
            ['name' => 'Kacang Almond Slice',          'unit' => 'gram', 'min_stock' => 1000,  'last_cost' => 240,  'stock' => 10000],
            ['name' => 'Bubuk Kayu Manis (Cinnamon)',  'unit' => 'gram', 'min_stock' => 500,   'last_cost' => 150,  'stock' => 5000],
            ['name' => 'Smoked Beef Slice',            'unit' => 'gram', 'min_stock' => 2000,  'last_cost' => 160,  'stock' => 20000],
            ['name' => 'Keju Mozzarella',              'unit' => 'gram', 'min_stock' => 3000,  'last_cost' => 130,  'stock' => 25000],
            ['name' => 'Beras Jasmine',                'unit' => 'gram', 'min_stock' => 20000, 'last_cost' => 18,   'stock' => 100000],
            ['name' => 'Daging Ayam Fillet',           'unit' => 'gram', 'min_stock' => 10000, 'last_cost' => 55,   'stock' => 60000],
            ['name' => 'Daging Sapi Shortplate',       'unit' => 'gram', 'min_stock' => 5000,  'last_cost' => 145,  'stock' => 40000],
            ['name' => 'Ikan Dori Fillet',             'unit' => 'gram', 'min_stock' => 5000,  'last_cost' => 60,   'stock' => 35000],
            ['name' => 'Ikan Salmon Fresh',            'unit' => 'gram', 'min_stock' => 3000,  'last_cost' => 280,  'stock' => 20000],
            ['name' => 'Pasta Spaghetti',              'unit' => 'gram', 'min_stock' => 5000,  'last_cost' => 38,   'stock' => 40000],
            ['name' => 'Cooking Cream',                'unit' => 'ml',   'min_stock' => 5000,  'last_cost' => 75,   'stock' => 30000],
            ['name' => 'Telur Ayam',                   'unit' => 'butir','min_stock' => 100,   'last_cost' => 2000, 'stock' => 800],
            ['name' => 'Bumbu Rempah & Minyak',        'unit' => 'porsi','min_stock' => 200,   'last_cost' => 2500, 'stock' => 2000],
        ];

        foreach ($items as $data) {
            $stock = $data['stock'];
            unset($data['stock']);

            $ingredient = Ingredient::updateOrCreate(
                ['name' => $data['name']],
                $data
            );

            $currentLedger = (float) StockMovement::where('ingredient_id', $ingredient->id)->sum('quantity');
            $diff = $stock - $currentLedger;

            if (abs($diff) > 0.001) {
                StockMovement::create([
                    'ingredient_id' => $ingredient->id,
                    'type'          => StockMovementType::Opname,
                    'quantity'      => $diff,
                    'stock_after'   => $stock,
                    'user_id'       => $admin?->id,
                    'note'          => 'Inisialisasi Stok Awal (Seeder)',
                    'created_at'    => now()->subDays(92),
                ]);
            }

            $ingredient->forceFill(['stock' => $stock])->save();
        }
    }
}
