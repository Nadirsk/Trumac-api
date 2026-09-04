<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userArray = [
            [
                // Super Admin
                'first_name' => 'Vijay',
                'last_name' => 'Kumar',
                'email' => 'KVJKUMR@GMAIL.COM',
                'phone' => '9579862371',
                'soft_password' => '123456',
                'position_id' => null,
                'company' => null,
                'role' => 1, // SUPER ADMIN
            ],
            [
                // Admin
                'first_name' => 'Portal',
                'last_name' => 'Admin',
                'email' => 'ADMIN@GMAIL.COM',
                'phone' => '919579862372',
                'soft_password' => '123456',
                'position_id' => null,
                'company' => 1,
                'role' => 2, // ADMIN
                'gender' => 'Male',
                'dob' => '2025-12-10',
                'address' => 'Sector 2 Shah Prima Kharghar',
                'pincode' => 400088,
            ],
            [
                // IT Team Leader
                'first_name' => 'Nadir',
                'last_name' => 'Shaikh',
                'email' => 'nadirsk@gmail.com',
                'phone' => '919326431979',
                'soft_password' => '123456',
                'position_id' => 1, // IT Team Leader
                'company' => 1,
                'role' => 4, // IT ADMIN
                'referral_code' => 'CEL4KB0P',
            ],
            [
                // IT Employee
                'first_name' => 'Vishal',
                'last_name' => 'Wagh',
                'email' => 'vishal@gmail.com',
                'phone' => '919870044033',
                'soft_password' => '123456',
                'position_id' => 2, // IT Employee
                'company' => 1,
                'role' => 4, // IT ADMIN
                'referral_code' => 'VM5PQADT',
            ],
            [
                // IT Purchase
                'first_name' => 'Vaibhav',
                'last_name' => 'Narale',
                'email' => 'vaibhav@gmail.com',
                'phone' => '919594738606',
                'soft_password' => '123456',
                'position_id' => 3, // IT Purchase
                'company' => 1,
                'role' => 4, // IT ADMIN
                'referral_code' => 'SZRPX10S',
            ],
            [
                // IT Driver
                'first_name' => 'Nikhil',
                'last_name' => 'Bhandhari',
                'email' => 'nikhil@gmail.com',
                'phone' => '918976606451',
                'soft_password' => '123456',
                'position_id' => 4, // IT Driver
                'company' => 1,
                'role' => 4, // IT ADMIN
                'referral_code' => 'BOEYNXKB',
            ],
            [
                // IT Sale
                'first_name' => 'Dilshad',
                'last_name' => 'Shaikh',
                'email' => 'dilshad@gmail.com',
                'phone' => '919365785012',
                'soft_password' => '123456',
                'position_id' => 5, // IT Sale
                'company' => 1,
                'role' => 4, // IT ADMIN
                'referral_code' => 'FTPJBAUM',
            ],
            [
                // IT Driver - Test User 2
                'first_name' => 'Rajesh',
                'last_name' => 'Kumar',
                'email' => 'rajesh.driver@gmail.com',
                'phone' => '919876543210',
                'soft_password' => '123456',
                'position_id' => 4, // IT Driver
                'company' => 1,
                'role' => 4, // IT ADMIN
                'gender' => 'Male',
                'address' => 'Mumbai, Maharashtra',
                'pincode' => 400001,
            ],
            [
                // IT Driver - Test User 3
                'first_name' => 'Amit',
                'last_name' => 'Patel',
                'email' => 'amit.driver@gmail.com',
                'phone' => '919876543211',
                'soft_password' => '123456',
                'position_id' => 4, // IT Driver
                'company' => 1,
                'role' => 4, // IT ADMIN
                'gender' => 'Male',
                'address' => 'Pune, Maharashtra',
                'pincode' => 411001,
            ],
            [
                // IT Driver - Test User 4
                'first_name' => 'Priya',
                'last_name' => 'Singh',
                'email' => 'priya.driver@gmail.com',
                'phone' => '919876543212',
                'soft_password' => '123456',
                'position_id' => 4, // IT Driver
                'company' => 1,
                'role' => 4, // IT ADMIN
                'gender' => 'Female',
                'address' => 'Bangalore, Karnataka',
                'pincode' => 560001,
            ],

            // ==================== WAREHOUSE ADMIN - ROLE ID 5 ====================

            [
                // Warehouse Admin
                'first_name' => 'Prajakta',
                'last_name' => 'Gaikwad',
                'email' => 'prajakta@gmail.com',
                'phone' => '919695949392',
                'soft_password' => '123456',
                'position_id' => 6, // Warehouse Admin
                'company' => 1,
                'role' => 5, // WAREHOUSE ADMIN
                'gender' => 'Female',
                'address' => 'Warehouse Complex, Pune',
                'pincode' => 411014,
                'referral_code' => 'WH01ADMIN',
                // 'warehouse_id' => 1,
            ],
            [
                // Warehouse Billing - User 1
                'first_name' => 'Priyanka',
                'last_name' => 'Deshmukh',
                'email' => 'priyanka.billing@gmail.com',
                'phone' => '919876501002',
                'soft_password' => '123456',
                'position_id' => 7, // Warehouse Billing
                'company' => 1,
                'role' => 5, // WAREHOUSE ADMIN
                'gender' => 'Female',
                'address' => 'Viman Nagar, Pune',
                'pincode' => 411014,
                'referral_code' => 'WH01BILL',
                // 'warehouse_id' => 1,
            ],
            [
                // Warehouse Billing - User 2
                'first_name' => 'Suresh',
                'last_name' => 'Patil',
                'email' => 'suresh.billing@gmail.com',
                'phone' => '919876501003',
                'soft_password' => '123456',
                'position_id' => 7, // Warehouse Billing
                'company' => 1,
                'role' => 5, // WAREHOUSE ADMIN
                'gender' => 'Male',
                'address' => 'Hadapsar, Pune',
                'pincode' => 411028,
                'referral_code' => 'WH02BILL',
                // 'warehouse_id' => 1,
            ],
            [
                // Warehouse Driver - User 1
                'first_name' => 'Ganesh',
                'last_name' => 'Kamble',
                'email' => 'ganesh.whdriver@gmail.com',
                'phone' => '919876501004',
                'soft_password' => '123456',
                'position_id' => 8, // Warehouse Driver
                'company' => 1,
                'role' => 5, // WAREHOUSE ADMIN
                'gender' => 'Male',
                'address' => 'Kharadi, Pune',
                'pincode' => 411014,
                'referral_code' => 'WH01DRV',
                // 'warehouse_id' => 1,
            ],
            [
                // Warehouse Driver - User 2
                'first_name' => 'Anil',
                'last_name' => 'Jadhav',
                'email' => 'anil.whdriver@gmail.com',
                'phone' => '919876501005',
                'soft_password' => '123456',
                'position_id' => 8, // Warehouse Driver
                'company' => 1,
                'role' => 5, // WAREHOUSE ADMIN
                'gender' => 'Male',
                'address' => 'Pimpri, Pune',
                'pincode' => 411018,
                'referral_code' => 'WH02DRV',
                // 'warehouse_id' => 1,
            ],
            [
                // Warehouse Driver - User 3
                'first_name' => 'Santosh',
                'last_name' => 'More',
                'email' => 'santosh.whdriver@gmail.com',
                'phone' => '919876501006',
                'soft_password' => '123456',
                'position_id' => 8, // Warehouse Driver
                'company' => 1,
                'role' => 5, // WAREHOUSE ADMIN
                'gender' => 'Male',
                'address' => 'Chinchwad, Pune',
                'pincode' => 411019,
                'referral_code' => 'WH03DRV',
                // 'warehouse_id' => 1,
            ],

            // ==================== CG ADMIN - ROLE ID 6 ====================

            [
                // CG Admin
                'first_name' => 'Rahul',
                'last_name' => 'Sharma',
                'email' => 'cgadmin@gmail.com',
                'phone' => '919876502001',
                'soft_password' => '123456',
                'position_id' => 9, // CG Admin
                'company' => 1,
                'role' => 6, // CG ADMIN
                'gender' => 'Male',
                'address' => 'CG Complex, Bangalore',
                'pincode' => 560001,
                'referral_code' => 'CG01ADMIN',
                // 'company_godown_id' => 1,
            ],
            [
                // CG Billing - User 1
                'first_name' => 'Anjali',
                'last_name' => 'Reddy',
                'email' => 'anjali.cgbilling@gmail.com',
                'phone' => '919876502002',
                'soft_password' => '123456',
                'position_id' => 10, // CG Billing
                'company' => 1,
                'role' => 6, // CG ADMIN
                'gender' => 'Female',
                'address' => 'Indiranagar, Bangalore',
                'pincode' => 560038,
                'referral_code' => 'CG01BILL',
                // 'company_godown_id' => 1,
            ],
            [
                // CG Billing - User 2
                'first_name' => 'Karthik',
                'last_name' => 'Nair',
                'email' => 'karthik.cgbilling@gmail.com',
                'phone' => '919876502003',
                'soft_password' => '123456',
                'position_id' => 10, // CG Billing
                'company' => 1,
                'role' => 6, // CG ADMIN
                'gender' => 'Male',
                'address' => 'Koramangala, Bangalore',
                'pincode' => 560034,
                'referral_code' => 'CG02BILL',
                // 'company_godown_id' => 1,
            ],
            [
                // CG Driver - User 1
                'first_name' => 'Sunil',
                'last_name' => 'Kumar',
                'email' => 'sunil.cgdriver@gmail.com',
                'phone' => '919876502004',
                'soft_password' => '123456',
                'position_id' => 11, // CG Driver
                'company' => 1,
                'role' => 6, // CG ADMIN
                'gender' => 'Male',
                'address' => 'Whitefield, Bangalore',
                'pincode' => 560066,
                'referral_code' => 'CG01DRV',
                // 'company_godown_id' => 1,
            ],
            [
                // CG Driver - User 2
                'first_name' => 'Ravi',
                'last_name' => 'Prasad',
                'email' => 'ravi.cgdriver@gmail.com',
                'phone' => '919876502005',
                'soft_password' => '123456',
                'position_id' => 11, // CG Driver
                'company' => 1,
                'role' => 6, // CG ADMIN
                'gender' => 'Male',
                'address' => 'Electronic City, Bangalore',
                'pincode' => 560100,
                'referral_code' => 'CG02DRV',
                // 'company_godown_id' => 1,
            ],
            [
                // CG Driver - User 3
                'first_name' => 'Lakshmi',
                'last_name' => 'Venkat',
                'email' => 'lakshmi.cgdriver@gmail.com',
                'phone' => '919876502006',
                'soft_password' => '123456',
                'position_id' => 11, // CG Driver
                'company' => 1,
                'role' => 6, // CG ADMIN
                'gender' => 'Female',
                'address' => 'Jayanagar, Bangalore',
                'pincode' => 560041,
                'referral_code' => 'CG03DRV',
                // 'company_godown_id' => 1,
            ],

            // ==================== FRANCHISE ADMIN - ROLE ID 7 ====================

            [
                // Franchise Admin
                'first_name' => 'Deepak',
                'last_name' => 'Gupta',
                'email' => 'franchiseadmin@gmail.com',
                'phone' => '919876503001',
                'soft_password' => '123456',
                'position_id' => 12, // Franchise Admin
                'company' => 1,
                'role' => 7, // FRANCHISE ADMIN
                'gender' => 'Male',
                'address' => 'Franchise Office, Delhi',
                'pincode' => 110001,
                'referral_code' => 'FR01ADMIN',
                // 'franchise_id' => 1,
            ],
            [
                // Franchise Billing - User 1
                'first_name' => 'Neha',
                'last_name' => 'Verma',
                'email' => 'neha.frbilling@gmail.com',
                'phone' => '919876503002',
                'soft_password' => '123456',
                'position_id' => 13, // Franchise Billing
                'company' => 1,
                'role' => 7, // FRANCHISE ADMIN
                'gender' => 'Female',
                'address' => 'Connaught Place, Delhi',
                'pincode' => 110001,
                'referral_code' => 'FR01BILL',
                // 'franchise_id' => 1,
            ],
            [
                // Franchise Billing - User 2
                'first_name' => 'Arjun',
                'last_name' => 'Singh',
                'email' => 'arjun.frbilling@gmail.com',
                'phone' => '919876503003',
                'soft_password' => '123456',
                'position_id' => 13, // Franchise Billing
                'company' => 1,
                'role' => 7, // FRANCHISE ADMIN
                'gender' => 'Male',
                'address' => 'Karol Bagh, Delhi',
                'pincode' => 110005,
                'referral_code' => 'FR02BILL',
                // 'franchise_id' => 1,
            ],
            [
                // Franchise Driver - User 1
                'first_name' => 'Manoj',
                'last_name' => 'Yadav',
                'email' => 'manoj.frdriver@gmail.com',
                'phone' => '919876503004',
                'soft_password' => '123456',
                'position_id' => 14, // Franchise Driver
                'company' => 1,
                'role' => 7, // FRANCHISE ADMIN
                'gender' => 'Male',
                'address' => 'Dwarka, Delhi',
                'pincode' => 110075,
                'referral_code' => 'FR01DRV',
                // 'franchise_id' => 1,
            ],
            [
                // Franchise Driver - User 2
                'first_name' => 'Ramesh',
                'last_name' => 'Chauhan',
                'email' => 'ramesh.frdriver@gmail.com',
                'phone' => '919876503005',
                'soft_password' => '123456',
                'position_id' => 14, // Franchise Driver
                'company' => 1,
                'role' => 7, // FRANCHISE ADMIN
                'gender' => 'Male',
                'address' => 'Rohini, Delhi',
                'pincode' => 110085,
                'referral_code' => 'FR02DRV',
                // 'franchise_id' => 1,
            ],
            [
                // Franchise Driver - User 3
                'first_name' => 'Pooja',
                'last_name' => 'Mehra',
                'email' => 'pooja.frdriver@gmail.com',
                'phone' => '919876503006',
                'soft_password' => '123456',
                'position_id' => 14, // Franchise Driver
                'company' => 1,
                'role' => 7, // FRANCHISE ADMIN
                'gender' => 'Female',
                'address' => 'Lajpat Nagar, Delhi',
                'pincode' => 110024,
                'referral_code' => 'FR03DRV',
                // 'franchise_id' => 1,
            ],

            // ==================== RETAILER - ROLE ID 3 (USER) ====================

            [
                // Retailer - User 1
                'first_name' => 'Rajiv',
                'last_name' => 'Mehta',
                'email' => 'rajiv.retailer@gmail.com',
                'phone' => '919876504001',
                'soft_password' => '123456',
                'position_id' => 15, // Retailer
                'company' => 1,
                'role' => 3, // USER (Retailer)
                'gender' => 'Male',
                'address' => 'Shop 101, MG Road, Bangalore',
                'pincode' => 560001,
                'referral_code' => 'RTL001',
                  // 'retailer_id' => 1,
            ],
            [
                // Retailer - User 2
                'first_name' => 'Sneha',
                'last_name' => 'Joshi',
                'email' => 'sneha.retailer@gmail.com',
                'phone' => '919876504002',
                'soft_password' => '123456',
                'position_id' => 15, // Retailer
                'company' => 1,
                'role' => 3, // USER (Retailer)
                'gender' => 'Female',
                'address' => 'Shop 45, Commercial Street, Bangalore',
                'pincode' => 560001,
                'referral_code' => 'RTL002',
                  // 'retailer_id' => 1,
            ],
            [
                // Retailer - User 3
                'first_name' => 'Vikram',
                'last_name' => 'Shah',
                'email' => 'vikram.retailer@gmail.com',
                'phone' => '919876504003',
                'soft_password' => '123456',
                'position_id' => 15, // Retailer
                'company' => 1,
                'role' => 3, // USER (Retailer)
                'gender' => 'Male',
                'address' => 'Shop 78, Brigade Road, Bangalore',
                'pincode' => 560025,
                'referral_code' => 'RTL003',
                  // 'retailer_id' => 1,
            ],
        ];

        foreach ($userArray as $user) {
            // Lookup position by name if position_name is provided
            $positionId = $user['position_id'] ?? null;
            if (isset($user['position_name'])) {
                $position = \App\Models\Position::where('name', $user['position_name'])
                    ->where('company_id', $user['company'])
                    ->first();
                $positionId = $position?->id;
            }

            $DBuser = User::withTrashed()->firstOrCreate(
                ['email' => $user['email']], // Check if the user already exists by email
                [
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                    'phone' => $user['phone'],
                    'password' => bcrypt($user['soft_password']),
                    'soft_password' => $user['soft_password'],
                    'position_id' => $positionId,
                    // 'warehouse_id' => $user['warehouse_id'] ?? null,
                    // 'company_godown_id' => $user['company_godown_id'] ?? null,
                    // 'franchise_id' => $user['franchise_id'] ?? null,
                    'referral_code' => $user['referral_code'] ?? null,
                    'gender' => $user['gender'] ?? null,
                    'dob' => $user['dob'] ?? null,
                    'address' => $user['address'] ?? null,
                    'pincode' => $user['pincode'] ?? null,
                ]
            );

            // Generate referral code if not provided
            if (!$DBuser->referral_code) {
                $DBuser->referral_code = $DBuser->generateReferralCode();
                $DBuser->save();
            }

            // Assign the role if it exists
            if (isset($user['role'])) {
                $DBuser->assignRole($user['role']);
            }

            // Assign the company if it exists
            if (isset($user['company'])) {
                $DBuser->assignCompany($user['company']);
            }
        }
    }
}
