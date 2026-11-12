<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [];
        foreach ([
            ['Beverages', 'blue'], ['Snacks', 'yellow'], ['Canned Goods', 'teal'],
            ['Personal Care', 'purple'], ['Household', 'orange'],
        ] as [$name, $color]) {
            $categories[$name] = Category::firstOrCreate(['name' => $name], ['color' => $color]);
        }

        $suppliers = [];
        foreach ([
            ['Coca-Cola Bottlers Phils.', 'Juan Dela Cruz', '0917-123-4567', 'Tarlac City'],
            ['Monde Nissin Corp.', 'Maria Santos', '0918-234-5678', 'San Isidro, Tarlac'],
            ['Unilever Philippines', 'Pedro Reyes', '0919-345-6789', 'Tarlac City'],
        ] as [$name, $contact, $phone, $address]) {
            $suppliers[$name] = Supplier::firstOrCreate(
                ['name' => $name],
                ['contact_person' => $contact, 'phone' => $phone, 'address' => $address]
            );
        }

        // sku, name, category, supplier (or null), cost, price, stock, reorder level
        foreach ([
            ['BEV-001', 'Coke 1.5L', 'Beverages', 'Coca-Cola Bottlers Phils.', 45, 65, 24, 10],
            ['BEV-002', 'Bottled Water 500mL', 'Beverages', 'Coca-Cola Bottlers Phils.', 8, 15, 50, 15],
            ['SNK-001', 'Lucky Me Pancit Canton', 'Snacks', 'Monde Nissin Corp.', 10, 15, 8, 20],
            ['SNK-002', 'Piattos Cheese 85g', 'Snacks', 'Monde Nissin Corp.', 22, 30, 30, 10],
            ['CAN-001', 'Century Tuna 155g', 'Canned Goods', null, 28, 38, 40, 12],
            ['PC-001', 'Safeguard Soap 90g', 'Personal Care', 'Unilever Philippines', 18, 25, 20, 10],
            ['HH-001', 'Surf Powder 66g', 'Household', 'Unilever Philippines', 6, 10, 60, 20],
        ] as [$sku, $name, $cat, $sup, $cost, $price, $stock, $reorder]) {
            Product::firstOrCreate(['sku' => $sku], [
                'name' => $name,
                'category_id' => $categories[$cat]->id,
                'supplier_id' => $sup ? $suppliers[$sup]->id : null,
                'cost_price' => $cost,
                'selling_price' => $price,
                'stock_quantity' => $stock,
                'reorder_level' => $reorder,
            ]);
        }
    }
}
