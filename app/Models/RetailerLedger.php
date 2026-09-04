<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetailerLedger extends Model
{
    use HasFactory;

    protected $table = 'retailer_ledger';

    protected $fillable = [
        'retailer_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'debit',
        'credit',
        'balance',
        'description',
        'transaction_date',
        'created_by',
        'company_id',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    // Transaction types
    const TYPE_SALE = 'sale';
    const TYPE_COLLECTION = 'collection';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_OPENING = 'opening';

    /**
     * Create ledger entry for sale
     */
    public static function recordSale(SalesOrder $order)
    {
        $currentBalance = self::getRetailerBalance($order->retailer_id);

        return self::create([
            'retailer_id' => $order->retailer_id,
            'transaction_type' => self::TYPE_SALE,
            'reference_type' => 'sales_order',
            'reference_id' => $order->id,
            'debit' => $order->total_amount,
            'credit' => 0,
            'balance' => $currentBalance + $order->total_amount,
            'description' => "Sale: Order #{$order->order_number}",
            'transaction_date' => $order->order_date,
            'company_id' => $order->company_id,
        ]);
    }

    /**
     * Create ledger entry for collection
     */
    public static function recordCollection(CashCollection $collection)
    {
        $currentBalance = self::getRetailerBalance($collection->retailer_id);

        return self::create([
            'retailer_id' => $collection->retailer_id,
            'transaction_type' => self::TYPE_COLLECTION,
            'reference_type' => 'cash_collection',
            'reference_id' => $collection->id,
            'debit' => 0,
            'credit' => $collection->amount_collected,
            'balance' => $currentBalance - $collection->amount_collected,
            'description' => "Collection: {$collection->payment_mode}",
            'transaction_date' => $collection->collection_date,
            'created_by' => $collection->collected_by,
            'company_id' => $collection->company_id,
        ]);
    }

    /**
     * Get retailer's current balance
     */
    public static function getRetailerBalance($retailerId): float
    {
        $lastEntry = self::where('retailer_id', $retailerId)
            ->orderBy('id', 'desc')
            ->first();

        return $lastEntry ? $lastEntry->balance : 0;
    }

    /**
     * Get retailer's statement
     */
    public static function getStatement($retailerId, $fromDate = null, $toDate = null)
    {
        $query = self::where('retailer_id', $retailerId)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        if ($fromDate) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }

        return $query->get();
    }

    // Relationships
    public function retailer()
    {
        return $this->belongsTo(Retailer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
