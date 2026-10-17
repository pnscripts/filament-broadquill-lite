<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->create(['name' => 'Demo Editor', 'email' => 'editor@demo.test', 'password' => bcrypt(str()->random(40))]);

        $cats = collect(['Home', 'Kitchen', 'Outdoor', 'Stationery'])->mapWithKeys(fn ($n) => [$n => Category::query()->create(['name' => $n])->id]);

        $rows = [
            ['Linen Cushion Cover', 'Home', 'active', 24.00, 120], ['Oak Serving Board', 'Kitchen', 'active', 39.50, 42],
            ['Enamel Camp Mug', 'Outdoor', 'draft', 14.90, 300], ['Dot Grid Notebook A5', 'Stationery', 'active', 12.00, 510],
            ['Stoneware Pasta Bowl', 'Kitchen', 'draft', 18.00, 96], ['Wool Picnic Blanket', 'Outdoor', 'draft', 79.00, 18],
            ['Brass Desk Lamp', 'Home', 'active', 129.00, 11], ['Glass Storage Jar 1 L', 'Kitchen', 'draft', 9.50, 240],
            ['Folding Trail Stool', 'Outdoor', 'archived', 34.00, 0], ['Recycled Kraft Envelopes', 'Stationery', 'draft', 6.50, 800],
            ['Cotton Tea Towel Set', 'Kitchen', 'active', 16.00, 150], ['Ceramic Plant Pot', 'Home', 'draft', 22.00, 64],
            ['Insulated Water Bottle', 'Outdoor', 'active', 27.00, 210], ['Fountain Pen Starter Kit', 'Stationery', 'draft', 45.00, 35],
            ['Walnut Wall Shelf', 'Home', 'archived', 89.00, 0], ['Cast Iron Skillet 26 cm', 'Kitchen', 'draft', 54.00, 28],
        ];

        foreach ($rows as $i => [$name, $cat, $status, $price, $stock]) {
            Product::query()->create([
                'name' => $name,
                'sku' => sprintf('PN-%04d', 1040 + $i * 7),
                'status' => $status,
                'category_id' => $cats[$cat],
                'is_featured' => $i % 5 === 1,
                'price' => $price,
                'stock' => $stock,
                'launch_date' => $status === 'active' ? now()->subDays(30 + $i * 4)->toDateString() : null,
            ]);
        }
    }
}
