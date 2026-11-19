<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CategoryProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoriesData = [
            [
                'name'       => 'Coffee',
                'slug'       => 'coffee',
                'subtitle'   => 'Pilihan biji arabika terbaik dari petani lokal nusantara',
                'sort_order' => 1,
                'products'   => [
                    [
                        'sku'         => 'COF-001',
                        'name'        => 'Espresso Single Origin',
                        'description' => 'Ekstraksi murni biji kopi Arabika dengan crema tebal dan aroma floral.',
                        'price'       => 18000,
                        'image_path'  => 'menu/espresso.jpg',
                        'sort_order'  => 1,
                    ],
                    [
                        'sku'         => 'COF-002',
                        'name'        => 'Classic Americano',
                        'description' => 'Double shot espresso dipadukan dengan air mineral panas atau es dingin menyegarkan.',
                        'price'       => 22000,
                        'image_path'  => 'menu/americano.jpg',
                        'sort_order'  => 2,
                    ],
                    [
                        'sku'         => 'COF-003',
                        'name'        => 'Hot Cappuccino',
                        'description' => 'Kombinasi seimbang espresso, steamed milk, dan foam susu tebal bertabur cocoa powder.',
                        'price'       => 28000,
                        'image_path'  => 'menu/cappuccino.jpg',
                        'sort_order'  => 3,
                    ],
                    [
                        'sku'         => 'COF-004',
                        'name'        => 'Flat White',
                        'description' => 'Espresso dengan micro-foam lembut yang menonjolkan rasa kopi yang kaya dan creamy.',
                        'price'       => 28000,
                        'image_path'  => 'menu/flat-white.jpg',
                        'sort_order'  => 4,
                    ],
                    [
                        'sku'         => 'COF-005',
                        'name'        => 'Iced Caffe Latte',
                        'description' => 'Susu segar dingin dipadukan espresso aromatik, lembut dan mudah dinikmati.',
                        'price'       => 28000,
                        'image_path'  => 'menu/ice-latte.jpg',
                        'sort_order'  => 5,
                    ],
                    [
                        'sku'         => 'COF-006',
                        'name'        => 'Piccolo Latte',
                        'description' => 'Ristretto kuat dengan sedikit susu hangat dalam cangkir kecil 100ml.',
                        'price'       => 24000,
                        'image_path'  => 'menu/piccolo.jpg',
                        'sort_order'  => 6,
                    ],
                    [
                        'sku'         => 'COF-007',
                        'name'        => 'Mochaccino Melt',
                        'description' => 'Paduan nikmat espresso, dark chocolate Belgia, dan susu segar creamy.',
                        'price'       => 32000,
                        'image_path'  => 'menu/mochaccino.jpg',
                        'sort_order'  => 7,
                    ],
                    [
                        'sku'         => 'COF-008',
                        'name'        => 'Classic Affogato',
                        'description' => 'Satu scoop es krim vanila disiram double shot espresso panas.',
                        'price'       => 26000,
                        'image_path'  => 'menu/affogato.jpg',
                        'sort_order'  => 8,
                    ],
                    [
                        'sku'         => 'COF-009',
                        'name'        => 'Kopi Susu Teduh',
                        'description' => 'Menu signature kopi susu creamy dengan racikan resep rahasia Ruang Teduh.',
                        'price'       => 20000,
                        'image_path'  => 'menu/kopi-susu.jpg',
                        'sort_order'  => 9,
                    ],
                    [
                        'sku'         => 'COF-010',
                        'name'        => 'Kopi Susu Gula Aren',
                        'description' => 'Espresso, susu segar, dan gula aren organik cair yang legit gurih.',
                        'price'       => 22000,
                        'image_path'  => 'menu/gula_aren.jpg',
                        'sort_order'  => 10,
                    ],
                    [
                        'sku'         => 'COF-011',
                        'name'        => 'Kopi Tubruk Robusta',
                        'description' => 'Seduhan tradisional biji robusta Lampung dengan body tebal dan rasa earthy.',
                        'price'       => 15000,
                        'image_path'  => 'menu/kopi-tubruk.jpg',
                        'sort_order'  => 11,
                    ],
                ],
            ],
            [
                'name'       => 'Non-Coffee',
                'slug'       => 'non-coffee',
                'subtitle'   => 'Minuman creamy, cokelat, dan blend menyegarkan tanpa kafein kopi',
                'sort_order' => 2,
                'products'   => [
                    [
                        'sku'         => 'NCF-001',
                        'name'        => 'Uji Matcha Latte',
                        'description' => 'Bubuk matcha murni asal Kyoto dipadu steamed milk hangat/dingin yang lembut.',
                        'price'       => 28000,
                        'image_path'  => 'menu/matcha.jpg',
                        'sort_order'  => 1,
                    ],
                    [
                        'sku'         => 'NCF-002',
                        'name'        => 'Signature Dark Chocolate',
                        'description' => 'Cokelat hitam premium pekat dengan sensasi rasa bittersweet mewah.',
                        'price'       => 28000,
                        'image_path'  => 'menu/chocolate.jpg',
                        'sort_order'  => 2,
                    ],
                    [
                        'sku'         => 'NCF-003',
                        'name'        => 'Velvet Bliss Latte',
                        'description' => 'Sentuhan rasa cake red velvet khas dengan vanila krim manis menggoda.',
                        'price'       => 28000,
                        'image_path'  => 'menu/red-velvet.jpg',
                        'sort_order'  => 3,
                    ],
                    [
                        'sku'         => 'NCF-004',
                        'name'        => 'Royal Taro Milk',
                        'description' => 'Taro wangi manis alami bersatu dengan susu murni yang lembut.',
                        'price'       => 26000,
                        'image_path'  => 'menu/taro.jpg',
                        'sort_order'  => 4,
                    ],
                    [
                        'sku'         => 'NCF-005',
                        'name'        => 'Choco Banana Frappe',
                        'description' => 'Blended cokelat, pisang segar cavendish, dan susu dengan topping whip cream.',
                        'price'       => 32000,
                        'image_path'  => 'menu/choco-banana.jpg',
                        'sort_order'  => 5,
                    ],
                    [
                        'sku'         => 'NCF-006',
                        'name'        => 'Fresh Strawberry Smoothies',
                        'description' => 'Smoothies buah stroberi asli dengan yogurt segar rendah lemak.',
                        'price'       => 30000,
                        'image_path'  => 'menu/strawberry-smoothies.jpg',
                        'sort_order'  => 6,
                    ],
                ],
            ],
            [
                'name'       => 'Tea & Refreshment',
                'slug'       => 'tea-refreshment',
                'subtitle'   => 'Seduhan teh artisan dan mocktail pelepas dahaga',
                'sort_order' => 3,
                'products'   => [
                    [
                        'sku'         => 'TEA-001',
                        'name'        => 'Authentic Thai Tea',
                        'description' => 'Teh Thailand orisinal dengan racikan susu evaporasi yang gurih manis.',
                        'price'       => 20000,
                        'image_path'  => 'menu/thai-tea.jpg',
                        'sort_order'  => 1,
                    ],
                    [
                        'sku'         => 'TEA-002',
                        'name'        => 'Green Tea Lemonade',
                        'description' => 'Perpaduan teh hijau melati dingin dan perasan lemon segar kaya antioksidan.',
                        'price'       => 22000,
                        'image_path'  => 'menu/green-tea-lemonade.jpg',
                        'sort_order'  => 2,
                    ],
                    [
                        'sku'         => 'TEA-003',
                        'name'        => 'Iced Lemon Tea',
                        'description' => 'Black tea klasik dengan irisan lemon asli dan gula tebu alami.',
                        'price'       => 18000,
                        'image_path'  => 'menu/lemon-tea.jpg',
                        'sort_order'  => 3,
                    ],
                    [
                        'sku'         => 'TEA-004',
                        'name'        => 'Iced Lychee Tea',
                        'description' => 'Teh melati dingin harum dengan buah leci utuh manis di dalamnya.',
                        'price'       => 24000,
                        'image_path'  => 'menu/lychee-tea.jpg',
                        'sort_order'  => 4,
                    ],
                ],
            ],
            [
                'name'       => 'Pastry & Bakery',
                'slug'       => 'pastry-bakery',
                'subtitle'   => 'Dipanggang fresh setiap pagi dengan butter Perancis pilihan',
                'sort_order' => 4,
                'products'   => [
                    [
                        'sku'         => 'BAK-001',
                        'name'        => 'French Butter Croissant',
                        'description' => 'Croissant berlapis renyah di luar, lembut dan wangi mentega di dalam.',
                        'price'       => 22000,
                        'image_path'  => 'menu/butter-croissant.jpg',
                        'sort_order'  => 1,
                    ],
                    [
                        'sku'         => 'BAK-002',
                        'name'        => 'Almond Flake Croissant',
                        'description' => 'Isian almond paste lembut dengan taburan almond panggang renyah berlimpah.',
                        'price'       => 28000,
                        'image_path'  => 'menu/almond-croissant.jpg',
                        'sort_order'  => 2,
                    ],
                    [
                        'sku'         => 'BAK-003',
                        'name'        => 'Pain au Chocolat',
                        'description' => 'Pastry renyah berlapis dengan lelehan dua stik cokelat hitam di dalamnya.',
                        'price'       => 26000,
                        'image_path'  => 'menu/pain-au-chocolat.jpg',
                        'sort_order'  => 3,
                    ],
                    [
                        'sku'         => 'BAK-004',
                        'name'        => 'Glazed Cinnamon Roll',
                        'description' => 'Roti gulung lembut beraroma kayu manis dengan topping gula glaze vanila.',
                        'price'       => 24000,
                        'image_path'  => 'menu/cinnamon-roll.jpg',
                        'sort_order'  => 4,
                    ],
                    [
                        'sku'         => 'BAK-005',
                        'name'        => 'Sweet Fruit Danish',
                        'description' => 'Danish pastry renyah diisi custard cream dan potongan buah manis.',
                        'price'       => 26000,
                        'image_path'  => 'menu/fruit-danish.jpg',
                        'sort_order'  => 5,
                    ],
                    [
                        'sku'         => 'BAK-006',
                        'name'        => 'Savory Quiche Lorraine',
                        'description' => 'Pie gurih hangat berisikan smoked beef, telur lembut, keju, dan rempah.',
                        'price'       => 32000,
                        'image_path'  => 'menu/quiche-lorraine.jpg',
                        'sort_order'  => 6,
                    ],
                    [
                        'sku'         => 'BAK-007',
                        'name'        => 'Smoked Beef Club Sandwich',
                        'description' => 'Roti gandum lapis smoked beef panggang, keju, selada, dan saus tartar.',
                        'price'       => 35000,
                        'image_path'  => 'menu/sandwich.jpg',
                        'sort_order'  => 7,
                    ],
                ],
            ],
            [
                'name'       => 'Main Course',
                'slug'       => 'main-course',
                'subtitle'   => 'Hidangan utama lezat & mengenyangkan untuk makan siang dan malam',
                'sort_order' => 5,
                'products'   => [
                    [
                        'sku'         => 'MNC-001',
                        'name'        => 'Nasi Goreng Spesial Teduh',
                        'description' => 'Nasi goreng bumbu rempah nusantara dengan suwiran ayam, telur mata sapi, dan kerupuk.',
                        'price'       => 38000,
                        'image_path'  => 'menu/nasi-goreng-kampung.jpg',
                        'sort_order'  => 1,
                    ],
                    [
                        'sku'         => 'MNC-002',
                        'name'        => 'Nasi Ayam Bakar Madu',
                        'description' => 'Paha ayam panggang karamelisasi madu gurih disajikan dengan nasi hangat dan sambal terasi.',
                        'price'       => 38000,
                        'image_path'  => 'menu/nasi-ayam.jpg',
                        'sort_order'  => 2,
                    ],
                    [
                        'sku'         => 'MNC-003',
                        'name'        => 'Ayam Geprek Sambal Bawang Mozzarella',
                        'description' => 'Ayam krispi digeprek sambal bawang pedas nampol dengan lelehan keju mozzarella.',
                        'price'       => 35000,
                        'image_path'  => 'menu/ayam-geprek-mozzarella.jpg',
                        'sort_order'  => 3,
                    ],
                    [
                        'sku'         => 'MNC-004',
                        'name'        => 'Ayam Bakar Taliwang Lombok',
                        'description' => 'Ayam bakar bumbu pedas khas Lombok dengan aroma terasi dan jeruk limau yang kuat.',
                        'price'       => 42000,
                        'image_path'  => 'menu/ayam-taliwang.jpg',
                        'sort_order'  => 4,
                    ],
                    [
                        'sku'         => 'MNC-005',
                        'name'        => 'Japanese Beef Gyudon Bowl',
                        'description' => 'Irisan daging sapi shortplate empuk dimasak saus mirin gurih di atas nasi jepang pulen.',
                        'price'       => 48000,
                        'image_path'  => 'menu/beef-gyudon.jpg',
                        'sort_order'  => 5,
                    ],
                    [
                        'sku'         => 'MNC-006',
                        'name'        => 'Salmon Mentai Rice Bowl',
                        'description' => 'Nasi bumbu nori dengan potongan salmon panggang lembut dan saus mentai bakar gurih.',
                        'price'       => 45000,
                        'image_path'  => 'menu/mentai-bowl.jpg',
                        'sort_order'  => 6,
                    ],
                    [
                        'sku'         => 'MNC-007',
                        'name'        => 'Chicken Nanban Tartar Bowl',
                        'description' => 'Ayam goreng krispi berlumur saus nanban asam manis dengan saus tartar telur segar.',
                        'price'       => 38000,
                        'image_path'  => 'menu/nanban-bowl.jpg',
                        'sort_order'  => 7,
                    ],
                    [
                        'sku'         => 'MNC-008',
                        'name'        => 'Crispy Dori Sambal Matah Bali',
                        'description' => 'Fillet ikan dori berbalut tepung renyah dengan siraman sambal matah serai segar.',
                        'price'       => 38000,
                        'image_path'  => 'menu/dori-sambal-matah.jpg',
                        'sort_order'  => 8,
                    ],
                    [
                        'sku'         => 'MNC-009',
                        'name'        => 'Spaghetti Creamy Carbonara',
                        'description' => 'Pasta al dente dalam saus krim lembut kaya keju parmesan dan irisan smoked beef gurih.',
                        'price'       => 42000,
                        'image_path'  => 'menu/spaghetti-carbonara.jpg',
                        'sort_order'  => 9,
                    ],
                    [
                        'sku'         => 'MNC-010',
                        'name'        => 'Spaghetti Aglio e Olio Tuna',
                        'description' => 'Pasta tumis bawang putih, minyak zaitun, cabai kering, dan potongan tuna lezat.',
                        'price'       => 36000,
                        'image_path'  => 'menu/spaghetti-olio.jpg',
                        'sort_order'  => 10,
                    ],
                    [
                        'sku'         => 'MNC-011',
                        'name'        => 'Truffle Cream Mushroom Pasta',
                        'description' => 'Pasta fettuccine dengan saus krim jamur beraroma minyak truffle putih yang mewah.',
                        'price'       => 45000,
                        'image_path'  => 'menu/pasta.jpg',
                        'sort_order'  => 11,
                    ],
                ],
            ],
        ];

        foreach ($categoriesData as $cData) {
            $products = $cData['products'];
            unset($cData['products']);

            $category = Category::updateOrCreate(['name' => $cData['name']], $cData);

            foreach ($products as $pData) {
                $pData['category_id'] = $category->id;
                $pData['is_active']   = true;
                $pData['show_on_web'] = true;

                Product::updateOrCreate(['sku' => $pData['sku']], $pData);
            }
        }
    }
}
