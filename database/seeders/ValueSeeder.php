<?php

namespace Database\Seeders;

use App\Models\Value;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $valueArray = [
            [
                'company_id'    => 1,
                'name'          => 'GENDER'
            ],
            [
                'company_id'    => 1,
                'name'          => 'DRIVER DOCUMENTS'
            ],
        ];

        foreach ($valueArray as $value) {
            Value::firstOrCreate(['name' => $value['name']], $value);
        }
    }
}
