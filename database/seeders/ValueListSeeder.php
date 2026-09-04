<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Value;
use App\Models\ValueList;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ValueListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run($companyId = '1'): void
    {
        if ($companyId) {
        } else {
            $companyId = 1;
        }
        $company_id = $companyId;
        $company = Company::find($company_id);
        $masterArray = [
            [
                'name' => 'STAFF DOCUMENT TYPE',
                'value_lists' => [
                    [
                        'description'   => 'ADHAAR CARD',
                        'code'          => 'ADHAAR CARD'
                    ],
                    [
                        'description'   => 'PAN CARD',
                        'code'          => 'PAN CARD'
                    ],
                    [
                        'description'   => 'PVC',
                        'code'          => 'PVC'
                    ],
                ],
            ],
            [
                'name' => 'GENDER',
                'value_lists' => [
                    [
                        'description'   => 'MALE',
                        'code'          => 'MALE'
                    ],
                    [
                        'description'   => 'FEMALE',
                        'code'          => 'FEMALE'
                    ],
                    [
                        'description'   => 'OTHER',
                        'code'          => 'OTHER'
                    ],
                ],
            ],
            [
                'name' => 'ZONE',
                'value_lists' => [
                    [
                        'description'   => 'NORTH',
                        'code'          => 'NORTH'
                    ],
                    [
                        'description'   => 'SOUTH',
                        'code'          => 'SOUTH'
                    ],
                    [
                        'description'   => 'EAST',
                        'code'          => 'EAST'
                    ],
                    [
                        'description'   => 'WEST',
                        'code'          => 'WEST'
                    ],
                ],
            ],
            [
                'name' => 'STATE',
                'value_lists' => [
                    [
                        'description'   => 'Andhra Pradesh',
                        'code'          => 'Andhra Pradesh'
                    ],
                    [
                        'description'   => 'Arunachal Pradesh',
                        'code'          => 'Arunachal Pradesh'
                    ],
                    [
                        'description'   => 'Assam',
                        'code'          => 'Assam'
                    ],
                    [
                        'description'   => 'Bihar',
                        'code'          => 'Bihar'
                    ],
                    [
                        'description'   => 'Chhattisgarh',
                        'code'          => 'Chhattisgarh'
                    ],
                    [
                        'description'   => 'Goa',
                        'code'          => 'Goa'
                    ],
                    [
                        'description'   => 'Gujarat',
                        'code'          => 'Gujarat'
                    ],
                    [
                        'description'   => 'Haryana',
                        'code'          => 'Haryana'
                    ],
                    [
                        'description'   => 'Himachal Pradesh',
                        'code'          => 'Himachal Pradesh'
                    ],
                    [
                        'description'   => 'Jharkhand',
                        'code'          => 'Jharkhand'
                    ],
                    [
                        'description'   => 'Karnataka',
                        'code'          => 'Karnataka'
                    ],
                    [
                        'description'   => 'Kerala',
                        'code'          => 'Kerala'
                    ],
                    [
                        'description'   => 'Madhya Pradesh',
                        'code'          => 'Madhya Pradesh'
                    ],
                    [
                        'description'   => 'Maharashtra',
                        'code'          => 'Maharashtra'
                    ],
                    [
                        'description'   => 'Manipur',
                        'code'          => 'Manipur'
                    ],
                    [
                        'description'   => 'Meghalaya',
                        'code'          => 'Meghalaya'
                    ],
                    [
                        'description'   => 'Mizoram',
                        'code'          => 'Mizoram'
                    ],
                    [
                        'description'   => 'Nagaland',
                        'code'          => 'Nagaland'
                    ],
                    [
                        'description'   => 'Odisha',
                        'code'          => 'Odisha'
                    ],
                    [
                        'description'   => 'Punjab',
                        'code'          => 'Punjab'
                    ],
                    [
                        'description'   => 'Rajasthan',
                        'code'          => 'Rajasthan'
                    ],
                    [
                        'description'   => 'Sikkim',
                        'code'          => 'Sikkim'
                    ],
                    [
                        'description'   => 'Tamil Nadu',
                        'code'          => 'Tamil Nadu'
                    ],
                    [
                        'description'   => 'Telangana',
                        'code'          => 'Telangana'
                    ],
                    [
                        'description'   => 'Tripura',
                        'code'          => 'Tripura'
                    ],
                    [
                        'description'   => 'Uttar Pradesh',
                        'code'          => 'Uttar Pradesh'
                    ],
                    [
                        'description'   => 'Uttarakhand',
                        'code'          => 'Uttarakhand'
                    ],
                    [
                        'description'   => 'West Bengal',
                        'code'          => 'West Bengal'
                    ],
                    [
                        'description'   => 'Andaman and Nicobar Islands',
                        'code'          => 'Andaman and Nicobar Islands'
                    ],
                    [
                        'description'   => 'Chandigarh',
                        'code'          => 'Chandigarh'
                    ],
                    [
                        'description'   => 'Dadra and Nagar Haveli',
                        'code'          => 'Dadra and Nagar Haveli'
                    ],
                    [
                        'description'   => 'Daman and Diu',
                        'code'          => 'Daman and Diu'
                    ],
                    [
                        'description'   => 'Delhi',
                        'code'          => 'Delhi'
                    ],
                    [
                        'description'   => 'Lakshadweep',
                        'code'          => 'Lakshadweep'
                    ],
                    [
                        'description'   => 'Puducherry',
                        'code'          => 'Puducherry'
                    ],
                    [
                        'description'   => 'Jammu and Kashmir',
                        'code'          => 'Jammu and Kashmir'
                    ],
                    [
                        'description'   => 'Ladakh',
                        'code'          => 'Ladakh'
                    ],
                ],
            ],
        ];

        foreach ($masterArray as $master) {
            // Check if the value with the given name exists or create it if not
            $DBvalue = Value::firstOrCreate(
                ['name' => $master['name']], // Unique criteria
                ['company_id' => $company->id] // Only used if creating
            );

            foreach ($master['value_lists'] as $key => $detail) {
                // Ensure value_list entry exists
                $DBvalue->value_lists()->firstOrCreate(
                    [
                        'company_id'  => $DBvalue->company_id,
                        'description' => $detail['description'],
                        'code'        => $detail['code'],
                    ]
                );
            }
        }
    }
}
