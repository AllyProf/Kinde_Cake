<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Item;
use App\Models\PackageUnit;
use App\Services\CatalogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NotebookCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = app(CatalogService::class);
        $catalog->importCategories();
        $catalog->importPackages();
        $catalog->importPaymentProviders();

        $units = [
            'kg' => $this->package('Kilogram', 'kg', 'Weight in kilograms'),
            'pcs' => $this->package('Piece', 'pcs', 'Individual pieces'),
            'L' => $this->package('Liter', 'L', 'Volume in liters'),
            'tray' => $this->package('Tray', 'tray', 'Tray or flat container'),
            'box' => $this->package('Box', 'box', 'Packaged in boxes'),
            'pack' => $this->package('Pack', 'pack', 'General pack unit'),
        ];

        $categories = [
            'cakes' => $this->category('Cakes', 'Celebration and specialty cakes'),
            'cookies' => $this->category('Cookies', 'Cookies and biscuits'),
            'snacks' => $this->category('Snacks', 'Snacks and fried items'),
            'drinks' => $this->category('Drinks', 'Soft drinks and juices'),
            'accessories' => $this->category('Accessories', 'Boxes, candles, gifts and party extras'),
        ];

        foreach ($this->sellableItems() as $row) {
            $category = $categories[$row['category']];
            $unit = $units[$row['unit']];

            $isCustom = (bool) ($row['is_custom'] ?? false);

            Item::updateOrCreate(
                ['name' => $row['name']],
                [
                    'category_id' => $category->id,
                    'package_unit_id' => $unit->id,
                    'price' => $isCustom ? 0 : $row['price'],
                    'sale_type' => $isCustom || ($row['category'] ?? '') === 'cakes'
                        ? Item::SALE_TYPE_ORDER
                        : Item::SALE_TYPE_RETAIL,
                    'is_custom' => $isCustom,
                    'description' => $row['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }

        foreach ($this->ingredients() as $row) {
            $receiving = $units[$row['receiving_unit']];
            $usage = $units[$row['usage_unit']];

            Ingredient::updateOrCreate(
                ['name' => $row['name']],
                [
                    'receiving_package_unit_id' => $receiving->id,
                    'usage_package_unit_id' => $usage->id,
                    'usage_per_receiving' => $row['usage_per_receiving'],
                    'stock_quantity' => 0,
                    'reorder_level' => $row['reorder_level'] ?? 0,
                    'description' => $row['description'] ?? null,
                    'is_active' => true,
                ]
            );
        }
    }

    /** @return list<array<string, mixed>> */
    private function sellableItems(): array
    {
        return [
            // Cakes (1 kg)
            ['name' => 'Chocolate Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 60000, 'description' => '1 kg'],
            ['name' => 'Vanilla Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 35000, 'description' => '1 kg'],
            ['name' => 'Black Forest Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 70000, 'description' => '1 kg'],
            ['name' => 'Fruit Caramel Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 70000, 'description' => '1 kg'],
            ['name' => 'Lemon Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 40000, 'description' => '1 kg'],
            ['name' => 'Coconut Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 40000, 'description' => '1 kg'],
            ['name' => 'Red Velvet Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 70000, 'description' => '1 kg'],
            ['name' => 'Dietary Banana Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 15000],
            ['name' => 'Honey & Atta Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 50000],
            ['name' => 'Peanut Butter Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 50000, 'description' => '1 kg'],
            ['name' => 'Tea Masala Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 50000],
            ['name' => 'Cookies & Cream (Oreo) Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 60000, 'description' => '1 kg'],
            ['name' => 'Passion Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 40000, 'description' => '1 kg'],
            ['name' => 'Strawberry Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 40000, 'description' => '1 kg'],
            ['name' => 'Butterscotch Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 50000],
            ['name' => 'Lemon Poppyseed Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 70000],
            ['name' => 'White Forest Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 70000],
            ['name' => 'Eggless Banana Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 60000],
            ['name' => 'Lemongrass Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 50000, 'description' => '1 kg'],
            ['name' => 'Carrot Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 60000, 'description' => '1 kg'],
            ['name' => 'Lemon Blueberry Cake', 'category' => 'cakes', 'unit' => 'kg', 'price' => 80000, 'description' => '1 kg'],
            ['name' => 'Mocha / Coffee Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 60000],
            ['name' => 'Eggless Carrot Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 60000],
            ['name' => 'Chocolate Eggless Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 50000],
            ['name' => 'Vanilla Eggless Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 40000],

            // Rojo / mini cakes
            ['name' => 'Rojo Chocolate', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 19000, 'description' => 'Rojo'],
            ['name' => 'Rojo Red Velvet', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 12000, 'description' => 'Rojo'],
            ['name' => 'Rojo Lemon', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 10000, 'description' => 'Rojo'],
            ['name' => 'Rojo Milk Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 12000, 'description' => 'Rojo'],
            ['name' => 'Vanilla + Coconut Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 40000],
            ['name' => 'Vanilla + Lemon Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 40000],
            ['name' => 'Plain Cake', 'category' => 'cakes', 'unit' => 'pcs', 'price' => 10000],

            // Cookies & snacks
            ['name' => 'Vanilla Cookies', 'category' => 'cookies', 'unit' => 'pcs', 'price' => 3000],
            ['name' => 'Chocolate Cookies', 'category' => 'cookies', 'unit' => 'pcs', 'price' => 5000],
            ['name' => 'Coconut Cookies', 'category' => 'cookies', 'unit' => 'pcs', 'price' => 4000],
            ['name' => 'Maandazi', 'category' => 'snacks', 'unit' => 'pcs', 'price' => 3000],

            // Drinks
            ['name' => 'Soda', 'category' => 'drinks', 'unit' => 'pcs', 'price' => 1000],
            ['name' => 'Grand Malta', 'category' => 'drinks', 'unit' => 'pcs', 'price' => 2500],
            ['name' => 'Shampein', 'category' => 'drinks', 'unit' => 'pcs', 'price' => 13000, 'description' => 'Champagne-style drink'],
            ['name' => 'Bomb', 'category' => 'drinks', 'unit' => 'pcs', 'price' => 10000],
            ['name' => 'Snow', 'category' => 'drinks', 'unit' => 'pcs', 'price' => 10000],
            ['name' => 'Juice (Azam)', 'category' => 'drinks', 'unit' => 'pcs', 'price' => 3500],
            ['name' => 'Juice (Ceres)', 'category' => 'drinks', 'unit' => 'pcs', 'price' => 6000],
            ['name' => 'Juice', 'category' => 'drinks', 'unit' => 'pcs', 'price' => 15000],

            // Accessories
            ['name' => 'Transparent Box', 'category' => 'accessories', 'unit' => 'pcs', 'price' => 10000],
            ['name' => 'Kofia', 'category' => 'accessories', 'unit' => 'pcs', 'price' => 13000],
            ['name' => 'Kofia (small)', 'category' => 'accessories', 'unit' => 'pcs', 'price' => 5000],
            ['name' => 'Sasha', 'category' => 'accessories', 'unit' => 'pcs', 'price' => 10000],
            ['name' => 'Mishumaa (candles)', 'category' => 'accessories', 'unit' => 'pcs', 'price' => 3000],
            ['name' => 'Mishumaa (small)', 'category' => 'accessories', 'unit' => 'pcs', 'price' => 2000],
            ['name' => 'Mifuko zawadi', 'category' => 'accessories', 'unit' => 'pcs', 'price' => 6000, 'description' => 'Gift bags'],
            ['name' => 'Surprise', 'category' => 'accessories', 'unit' => 'pcs', 'price' => 15000],
            ['name' => 'Plates + Cup', 'category' => 'accessories', 'unit' => 'pack', 'price' => 10000],
            ['name' => 'Pipi toys', 'category' => 'accessories', 'unit' => 'pcs', 'price' => 15000],
            ['name' => 'Tik Tok', 'category' => 'accessories', 'unit' => 'pcs', 'price' => 12000],

            // Custom order template (name + price set on each sale)
            [
                'name' => 'Custom Cake',
                'category' => 'cakes',
                'unit' => 'pcs',
                'price' => 0,
                'is_custom' => true,
                'description' => 'Custom cake — enter customer cake name and price on the sale',
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function ingredients(): array
    {
        return [
            [
                'name' => 'Unga (flour) 25kg',
                'receiving_unit' => 'kg',
                'usage_unit' => 'kg',
                'usage_per_receiving' => 25,
                'reorder_level' => 5,
                'description' => 'Purchase note: ~58,000 TZS / 25 kg',
            ],
            [
                'name' => 'Prestige',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'reorder_level' => 2,
                'description' => 'Purchase note: ~68,000 TZS',
            ],
            [
                'name' => 'Mayai (eggs)',
                'receiving_unit' => 'tray',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 30,
                'reorder_level' => 30,
                'description' => 'Purchase note: ~7,500 TZS / tray',
            ],
            [
                'name' => 'Kimbo 4kg',
                'receiving_unit' => 'kg',
                'usage_unit' => 'kg',
                'usage_per_receiving' => 4,
                'reorder_level' => 4,
                'description' => 'Purchase note: ~29,000 TZS / 4 kg',
            ],
            [
                'name' => 'Sukari (sugar) 5kg',
                'receiving_unit' => 'kg',
                'usage_unit' => 'kg',
                'usage_per_receiving' => 5,
                'reorder_level' => 5,
                'description' => 'Purchase note: ~210,000 TZS / 5 kg (as listed)',
            ],
            [
                'name' => 'Safi oil 5L',
                'receiving_unit' => 'L',
                'usage_unit' => 'L',
                'usage_per_receiving' => 5,
                'reorder_level' => 5,
                'description' => 'Price not listed in notebook',
            ],
            [
                'name' => 'Unga Azam',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'reorder_level' => 2,
                'description' => 'Purchase note: ~42,000 TZS',
            ],
            [
                'name' => 'Whipping cream',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'reorder_level' => 2,
                'description' => 'Purchase note: ~20,000 TZS',
            ],
            [
                'name' => 'Sukari brown',
                'receiving_unit' => 'kg',
                'usage_unit' => 'kg',
                'usage_per_receiving' => 1,
                'reorder_level' => 1,
                'description' => 'Purchase note: ~3,000 TZS / kg',
            ],
            [
                'name' => 'Icing sugar',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'reorder_level' => 1,
                'description' => 'Purchase note: 100,000 – 125,000 TZS',
            ],
            [
                'name' => 'Cake base 8"',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'description' => 'Supply item from notebook',
            ],
            [
                'name' => 'Cake base 10"',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'description' => 'Supply item from notebook',
            ],
            [
                'name' => 'Cake base 12"',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'description' => 'Supply item from notebook',
            ],
            [
                'name' => 'Cake box (supply)',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'description' => 'Supply item from notebook',
            ],
            [
                'name' => 'Balls (decor)',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'description' => 'Supply item from notebook',
            ],
            [
                'name' => 'Flowers (decor)',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'description' => 'Supply item from notebook',
            ],
            [
                'name' => 'Sprinkles',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'description' => 'Supply item from notebook',
            ],
            [
                'name' => 'Wafer papers',
                'receiving_unit' => 'pcs',
                'usage_unit' => 'pcs',
                'usage_per_receiving' => 1,
                'description' => 'Supply item from notebook',
            ],
        ];
    }

    private function category(string $name, string $description): Category
    {
        return Category::updateOrCreate(
            ['slug' => Str::slug($name)],
            [
                'name' => $name,
                'description' => $description,
                'is_builtin' => false,
                'is_active' => true,
            ]
        );
    }

    private function package(string $name, string $symbol, string $description): PackageUnit
    {
        return PackageUnit::updateOrCreate(
            ['symbol' => $symbol],
            [
                'name' => $name,
                'description' => $description,
                'is_builtin' => true,
                'is_active' => true,
            ]
        );
    }
}
