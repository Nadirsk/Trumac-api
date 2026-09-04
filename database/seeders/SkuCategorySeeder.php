<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SkuCategory;
use Illuminate\Database\Seeder;

class SkuCategorySeeder extends Seeder
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

        $categories = [
            [
                'name' => 'Beverages',
                'description' => 'Soft drinks, juices, water, and energy drinks',
                'is_active' => true,
                'is_deleted' => false,
                'company_id' => $company->id,
            ],
            [
                'name' => 'Snacks',
                'description' => 'Chips, biscuits, cookies, and namkeen',
                'is_active' => true,
                'is_deleted' => false,
                'company_id' => $company->id,
            ],
            [
                'name' => 'Personal Care',
                'description' => 'Soaps, shampoos, toothpaste, and skincare products',
                'is_active' => true,
                'is_deleted' => false,
                'company_id' => $company->id,
            ],
            [
                'name' => 'Dairy Products',
                'description' => 'Milk, curd, butter, cheese, and paneer',
                'is_active' => true,
                'is_deleted' => false,
                'company_id' => $company->id,
            ],
            [
                'name' => 'Household Items',
                'description' => 'Cleaning supplies, detergents, and home essentials',
                'is_active' => true,
                'is_deleted' => false,
                'company_id' => $company->id,
            ],
        ];

        foreach ($categories as $category) {
            SkuCategory::firstOrCreate(
                ['name' => $category['name'], 'company_id' => $category['company_id']],
                $category
            );
        }

        $this->command->info('SKU Categories seeded successfully!');
    }
}
