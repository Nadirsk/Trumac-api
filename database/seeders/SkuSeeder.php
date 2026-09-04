<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Sku;
use App\Models\SkuCategory;
use Illuminate\Database\Seeder;

class SkuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();

        if (!$company) {
            $this->command->error('No company found. Please run CompanySeeder first.');
            return;
        }

        // Get categories (run SkuCategorySeeder first if needed)
        $beverages = SkuCategory::where('name', 'Beverages')->where('company_id', $company->id)->first();
        $snacks = SkuCategory::where('name', 'Snacks')->where('company_id', $company->id)->first();
        $personalCare = SkuCategory::where('name', 'Personal Care')->where('company_id', $company->id)->first();
        $dairy = SkuCategory::where('name', 'Dairy Products')->where('company_id', $company->id)->first();
        $household = SkuCategory::where('name', 'Household Items')->where('company_id', $company->id)->first();

        $skus = [
            [
                'code' => 'BEV001',
                'name' => 'Coca Cola 500ml',
                'description' => 'Carbonated soft drink 500ml bottle',
                'unit' => 'Bottle',
                'mrp' => 40.00,
                'selling_price' => 38.00,
                'purchase_price' => 32.00,
                'hsn_code' => '22021010',
                'gst_percent' => 18.00,
                'out_of_stock_threshold' => 50,
                'barcode' => '8901234567890',
                'category_id' => $beverages?->id,
                'is_active' => true,
                'is_deleted' => false,
                'company_id' => $company->id,
            ],
            [
                'code' => 'SNK001',
                'name' => 'Lays Classic Salted 52g',
                'description' => 'Classic salted potato chips 52g pack',
                'unit' => 'Pack',
                'mrp' => 20.00,
                'selling_price' => 20.00,
                'purchase_price' => 16.00,
                'hsn_code' => '20052000',
                'gst_percent' => 12.00,
                'out_of_stock_threshold' => 100,
                'barcode' => '8901234567891',
                'category_id' => $snacks?->id,
                'is_active' => true,
                'is_deleted' => false,
                'company_id' => $company->id,
            ],
            [
                'code' => 'PER001',
                'name' => 'Dove Soap 100g',
                'description' => 'Moisturizing beauty bar soap 100g',
                'unit' => 'Piece',
                'mrp' => 55.00,
                'selling_price' => 52.00,
                'purchase_price' => 44.00,
                'hsn_code' => '34011110',
                'gst_percent' => 18.00,
                'out_of_stock_threshold' => 30,
                'barcode' => '8901234567892',
                'category_id' => $personalCare?->id,
                'is_active' => true,
                'is_deleted' => false,
                'company_id' => $company->id,
            ],
            [
                'code' => 'DAI001',
                'name' => 'Amul Butter 100g',
                'description' => 'Pasteurized butter 100g pack',
                'unit' => 'Pack',
                'mrp' => 56.00,
                'selling_price' => 54.00,
                'purchase_price' => 48.00,
                'hsn_code' => '04051000',
                'gst_percent' => 5.00,
                'out_of_stock_threshold' => 40,
                'barcode' => '8901234567893',
                'category_id' => $dairy?->id,
                'is_active' => true,
                'is_deleted' => false,
                'company_id' => $company->id,
            ],
            [
                'code' => 'HOU001',
                'name' => 'Surf Excel 1kg',
                'description' => 'Washing powder detergent 1kg pack',
                'unit' => 'Pack',
                'mrp' => 185.00,
                'selling_price' => 175.00,
                'purchase_price' => 150.00,
                'hsn_code' => '34022090',
                'gst_percent' => 18.00,
                'out_of_stock_threshold' => 25,
                'barcode' => '8901234567894',
                'category_id' => $household?->id,
                'is_active' => true,
                'is_deleted' => false,
                'company_id' => $company->id,
            ],
        ];

        foreach ($skus as $sku) {
            Sku::firstOrCreate(
                ['code' => $sku['code'], 'company_id' => $sku['company_id']],
                $sku
            );
        }

        $this->command->info('SKUs seeded successfully!');
    }
}
