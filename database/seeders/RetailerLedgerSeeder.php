<?php

namespace Database\Seeders;

use App\Models\RetailerLedger;
use App\Models\Retailer;
use Illuminate\Database\Seeder;

class RetailerLedgerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $retailers = Retailer::where('is_deleted', false)->get();

        if ($retailers->isEmpty()) {
            return;
        }

        $ledgerEntries = [];

        foreach ($retailers->take(5) as $retailer) {
            $balance = 0;

            // Opening balance
            $openingBalance = rand(5000, 10000);
            $balance = $openingBalance;
            $ledgerEntries[] = [
                'company_id' => $retailer->company_id,
                'retailer_id' => $retailer->id,
                'transaction_date' => now()->subDays(30),
                'transaction_type' => 'opening',
                'debit' => $openingBalance,
                'credit' => 0,
                'balance' => $balance,
                'description' => 'Opening balance',
            ];

            // Sale transaction (increases amount owed by retailer - DEBIT)
            $saleAmount = rand(10000, 30000);
            $balance += $saleAmount;
            $ledgerEntries[] = [
                'company_id' => $retailer->company_id,
                'retailer_id' => $retailer->id,
                'transaction_date' => now()->subDays(15),
                'transaction_type' => 'sale',
                'reference_type' => 'sales_order',
                'reference_id' => rand(1, 10),
                'debit' => $saleAmount,
                'credit' => 0,
                'balance' => $balance,
                'description' => 'Sales order - goods delivered',
            ];

            // Collection transaction (payment by retailer - CREDIT)
            $collectionAmount = rand(5000, 15000);
            $balance -= $collectionAmount;
            $ledgerEntries[] = [
                'company_id' => $retailer->company_id,
                'retailer_id' => $retailer->id,
                'transaction_date' => now()->subDays(5),
                'transaction_type' => 'collection',
                'reference_type' => 'cash_collection',
                'reference_id' => rand(1, 10),
                'debit' => 0,
                'credit' => $collectionAmount,
                'balance' => $balance,
                'description' => 'Payment collected from retailer',
            ];
        }

        foreach ($ledgerEntries as $entry) {
            RetailerLedger::create($entry);
        }
    }
}
