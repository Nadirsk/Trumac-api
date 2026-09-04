<?php

namespace Database\Seeders;

use App\Models\CashCollection;
use App\Models\Retailer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CashCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $retailers = Retailer::where('is_deleted', false)->get();
        $users = User::whereNotNull('position_id')->first();

        if ($retailers->isEmpty() || !$users) {
            return;
        }

        $collections = [];

        foreach ($retailers->take(5) as $retailer) {
            $amountDue = rand(10000, 30000);
            $amountCollected = rand(5000, $amountDue);

            // Collected payment
            $collections[] = [
                'company_id' => 1,
                'retailer_id' => $retailer->id,
                'collected_by' => $users->id,
                'collection_date' => now()->subDays(rand(1, 10)),
                'amount_due' => $amountDue,
                'amount_collected' => $amountDue, // Fully collected
                'status' => 'collected',
                'payment_mode' => ['cash', 'cheque', 'bank_transfer', 'upi'][rand(0, 3)],
                'payment_reference' => 'REF-' . strtoupper(substr(md5(rand()), 0, 10)),
                'notes' => 'Payment collected successfully',
                'latitude' => 19.0760 + (rand(-100, 100) / 10000),
                'longitude' => 72.8777 + (rand(-100, 100) / 10000),
                'is_deleted' => false,
            ];

            // Partial payment
            $collections[] = [
                'company_id' => 1,
                'retailer_id' => $retailer->id,
                'collected_by' => $users->id,
                'collection_date' => now()->subDays(rand(11, 20)),
                'amount_due' => $amountDue,
                'amount_collected' => $amountCollected, // Partial
                'status' => 'partial',
                'payment_mode' => 'cash',
                'notes' => 'Partial payment received, remaining amount to be collected',
                'next_collection_date' => now()->addDays(rand(1, 7)),
                'is_deleted' => false,
            ];
        }

        foreach ($collections as $collection) {
            CashCollection::create($collection);
        }
    }
}
