<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = [
            [
                'company_id' => 1,
                'name' => 'Supreme Medical Suppliers',
                'contact_person' => 'Ramesh Kumar',
                'phone' => '9876501234',
                'email' => 'contact@suprememedical.com',
                'address' => 'Plot 12, MIDC',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400001',
                'gst_no' => '27AAAAA0000A1Z5',
                'pan_no' => 'AAAAA0000A',
                'bank_name' => 'HDFC Bank',
                'account_no' => '50100123456789',
                'ifsc' => 'HDFC0001234',
                'branch_name' => 'Andheri West',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'HealthCare Distributors',
                'contact_person' => 'Priya Mehta',
                'phone' => '9876501235',
                'email' => 'info@healthcaredist.com',
                'address' => '45, Industrial Estate',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '411014',
                'gst_no' => '27BBBBB0000B1Z5',
                'pan_no' => 'BBBBB0000B',
                'bank_name' => 'ICICI Bank',
                'account_no' => '60200123456789',
                'ifsc' => 'ICIC0001234',
                'branch_name' => 'Shivaji Nagar',
                'is_active' => true,
                'is_deleted' => false,
            ],
            [
                'company_id' => 1,
                'name' => 'MediPharm Solutions',
                'contact_person' => 'Vijay Singh',
                'phone' => '9876501236',
                'email' => 'sales@medipharm.com',
                'address' => 'Sector 15, CBD',
                'city' => 'Navi Mumbai',
                'state' => 'Maharashtra',
                'pincode' => '400614',
                'gst_no' => '27CCCCC0000C1Z5',
                'pan_no' => 'CCCCC0000C',
                'bank_name' => 'SBI',
                'account_no' => '30100123456789',
                'ifsc' => 'SBIN0001234',
                'branch_name' => 'Vashi',
                'is_active' => true,
                'is_deleted' => false,
            ],
        ];

        foreach ($vendors as $vendor) {
            Vendor::firstOrCreate(
                [
                    'gst_no' => $vendor['gst_no'],
                    'company_id' => $vendor['company_id'],
                ],
                $vendor
            );
        }
    }
}
