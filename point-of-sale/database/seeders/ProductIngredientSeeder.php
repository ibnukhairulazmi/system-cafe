<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductIngredientSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all()->keyBy('sku');
        $ingredients = Ingredient::all()->keyBy('name');

        $recipes = [
            'COF-001' => [['Biji Kopi Arabika House Blend', 18]],
            'COF-002' => [['Biji Kopi Arabika House Blend', 18]],
            'COF-003' => [['Biji Kopi Arabika House Blend', 18], ['Susu UHT Full Cream', 180]],
            'COF-004' => [['Biji Kopi Arabika House Blend', 18], ['Susu UHT Full Cream', 150]],
            'COF-005' => [['Biji Kopi Arabika House Blend', 18], ['Susu UHT Full Cream', 200]],
            'COF-006' => [['Biji Kopi Arabika House Blend', 14], ['Susu UHT Full Cream', 80]],
            'COF-007' => [['Biji Kopi Arabika House Blend', 18], ['Susu UHT Full Cream', 150], ['Bubuk Cokelat Premium', 20]],
            'COF-008' => [['Biji Kopi Arabika House Blend', 18], ['Cooking Cream', 50]],
            'COF-009' => [['Biji Kopi Arabika House Blend', 18], ['Susu UHT Full Cream', 120], ['Susu Evaporasi', 30]],
            'COF-010' => [['Biji Kopi Arabika House Blend', 18], ['Susu UHT Full Cream', 140], ['Gula Aren Cair', 25]],
            'COF-011' => [['Biji Kopi Robusta', 20]],

            'NCF-001' => [['Bubuk Matcha Uji', 25], ['Susu UHT Full Cream', 200]],
            'NCF-002' => [['Bubuk Cokelat Premium', 35], ['Susu UHT Full Cream', 200]],
            'NCF-003' => [['Bubuk Red Velvet', 30], ['Susu UHT Full Cream', 200]],
            'NCF-004' => [['Bubuk Taro', 30], ['Susu UHT Full Cream', 200]],
            'NCF-005' => [['Bubuk Cokelat Premium', 25], ['Pisang Cavendish', 100], ['Susu UHT Full Cream', 150]],
            'NCF-006' => [['Buah Strawberry Segar', 120], ['Susu UHT Full Cream', 100]],

            'TEA-001' => [['Daun Teh Hitam', 20], ['Susu Evaporasi', 50], ['Susu UHT Full Cream', 100]],
            'TEA-002' => [['Daun Teh Hijau', 15], ['Lemon Segar', 30]],
            'TEA-003' => [['Daun Teh Hitam', 15], ['Lemon Segar', 30]],
            'TEA-004' => [['Daun Teh Hitam', 15], ['Sirup Leci', 30]],

            'BAK-001' => [['Tepung Terigu Protein Tinggi', 80], ['Mentega / Butter Anchor', 45]],
            'BAK-002' => [['Tepung Terigu Protein Tinggi', 80], ['Mentega / Butter Anchor', 45], ['Kacang Almond Slice', 20]],
            'BAK-003' => [['Tepung Terigu Protein Tinggi', 80], ['Mentega / Butter Anchor', 45], ['Cokelat Batang Callebaut', 30]],
            'BAK-004' => [['Tepung Terigu Protein Tinggi', 80], ['Mentega / Butter Anchor', 30], ['Bubuk Kayu Manis (Cinnamon)', 10]],
            'BAK-005' => [['Tepung Terigu Protein Tinggi', 80], ['Mentega / Butter Anchor', 35], ['Buah Strawberry Segar', 30]],
            'BAK-006' => [['Tepung Terigu Protein Tinggi', 60], ['Smoked Beef Slice', 30], ['Telur Ayam', 1], ['Keju Mozzarella', 30]],
            'BAK-007' => [['Tepung Terigu Protein Tinggi', 60], ['Smoked Beef Slice', 40], ['Telur Ayam', 1], ['Keju Mozzarella', 25]],

            'MNC-001' => [['Beras Jasmine', 150], ['Telur Ayam', 1], ['Daging Ayam Fillet', 50], ['Bumbu Rempah & Minyak', 1]],
            'MNC-002' => [['Beras Jasmine', 150], ['Daging Ayam Fillet', 150], ['Bumbu Rempah & Minyak', 1]],
            'MNC-003' => [['Beras Jasmine', 150], ['Daging Ayam Fillet', 150], ['Keju Mozzarella', 40], ['Bumbu Rempah & Minyak', 1]],
            'MNC-004' => [['Beras Jasmine', 150], ['Daging Ayam Fillet', 180], ['Bumbu Rempah & Minyak', 1]],
            'MNC-005' => [['Beras Jasmine', 150], ['Daging Sapi Shortplate', 120], ['Bumbu Rempah & Minyak', 1]],
            'MNC-006' => [['Beras Jasmine', 150], ['Ikan Salmon Fresh', 100], ['Bumbu Rempah & Minyak', 1]],
            'MNC-007' => [['Beras Jasmine', 150], ['Daging Ayam Fillet', 130], ['Telur Ayam', 1], ['Bumbu Rempah & Minyak', 1]],
            'MNC-008' => [['Beras Jasmine', 150], ['Ikan Dori Fillet', 140], ['Bumbu Rempah & Minyak', 1]],
            'MNC-009' => [['Pasta Spaghetti', 100], ['Cooking Cream', 60], ['Telur Ayam', 1], ['Smoked Beef Slice', 30], ['Keju Mozzarella', 20]],
            'MNC-010' => [['Pasta Spaghetti', 100], ['Bumbu Rempah & Minyak', 1]],
            'MNC-011' => [['Pasta Spaghetti', 100], ['Cooking Cream', 70], ['Keju Mozzarella', 25]],
        ];

        foreach ($recipes as $sku => $items) {
            $product = $products->get($sku);
            if (! $product) {
                continue;
            }

            foreach ($items as [$ingName, $qty]) {
                $ing = $ingredients->get($ingName);
                if (! $ing) {
                    continue;
                }

                DB::table('product_ingredients')->updateOrInsert(
                    ['product_id' => $product->id, 'ingredient_id' => $ing->id],
                    ['quantity' => $qty]
                );
            }
        }
    }
}
