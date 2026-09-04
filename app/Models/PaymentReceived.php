<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceived extends Model
{
    use HasFactory;

    protected $table = 'payments_received';

    public static $searchable = SearchModelParams::PaymentReceived;

    const STATUS_PAID = 'paid';
    const STATUS_REFUNDED = 'refunded';

    const MODE_CASH = 'cash';
    const MODE_BANK_TRANSFER = 'bank_transfer';
    const MODE_UPI = 'upi';
    const MODE_CHEQUE = 'cheque';

    protected $fillable = [
        'payment_number',
        'invoice_id',
        'retailer_id',
        'payment_date',
        'amount',
        'payment_mode',
        'reference_number',
        'notes',
        'status',
        'created_by',
        'company_id',
        'is_deleted',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Generate payment number: PAY-REC{YYYYMMDD}{0001}
     */
    public static function generatePaymentNumber($companyId): string
    {
        $prefix = 'REC' . now()->format('Ymd');
        $last = self::where('company_id', $companyId)
            ->where('payment_number', 'like', $prefix . '%')
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($last) {
            $lastNumber = (int) substr($last->payment_number, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }

    /**
     * Record payment against an invoice
     */
    public static function recordPayment(Invoice $invoice, array $data, $createdBy, $companyId): self
    {
        $payment = self::create([
            'payment_number' => self::generatePaymentNumber($companyId),
            'invoice_id' => $invoice->id,
            'retailer_id' => $invoice->retailer_id,
            'payment_date' => $data['payment_date'] ?? now(),
            'amount' => $data['amount'],
            'payment_mode' => $data['payment_mode'] ?? self::MODE_CASH,
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => self::STATUS_PAID,
            'created_by' => $createdBy,
            'company_id' => $companyId,
        ]);

        // Update invoice payment totals
        $invoice->amount_paid += $data['amount'];
        $invoice->updatePaymentStatus();

        return $payment;
    }

    // Relationships

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('is_deleted', false);
    }
}
