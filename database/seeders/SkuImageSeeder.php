<?php

namespace Database\Seeders;

use App\Models\SkuImage;
use App\Models\Sku;
use Illuminate\Database\Seeder;

class SkuImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skus = Sku::where('is_deleted', false)->take(10)->get();

        if ($skus->isEmpty()) {
            return;
        }

        $images = [];

        foreach ($skus as $index => $sku) {
            // Primary image
            $images[] = [
                'sku_id' => $sku->id,
                'image_path' => 'skus/images/' . $sku->code . '_primary.jpg',
                'is_primary' => true,
                'sort_order' => 1,
            ];

            // Additional images
            for ($i = 2; $i <= 3; $i++) {
                $images[] = [
                    'sku_id' => $sku->id,
                    'image_path' => 'skus/images/' . $sku->code . '_' . $i . '.jpg',
                    'is_primary' => false,
                    'sort_order' => $i,
                ];
            }
        }

        foreach ($images as $image) {
            SkuImage::create($image);
        }
    }
}
