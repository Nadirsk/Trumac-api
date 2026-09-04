<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyArray = [
            [
                'name'       => 'Trumac Pvt. Ltd.',
                'email'      => 'ADMINPORTAL@GMAIL.COM',
                'address'    => 'Office no: 502, Shah Prima, Sec 2, Kharghar, Navi Mumbai - 410210',
                'phone'      => '9579862371',
                'admin_name' => 'Portal Admin',
            ]
        ];

        foreach ($companyArray as $company) {
            // Check if the company with the given email exists or create it if not
            $DBcompany = Company::firstOrCreate(['email' => $company['email']], $company);
        }
    }
}
