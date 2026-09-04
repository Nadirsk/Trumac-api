<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        @page {
            margin: 8mm;
        }
        .container {
            padding: 5px;
        }

        /* Header */
        .header-table {
            width: 100%;
            border: 1px solid #333;
            border-collapse: collapse;
        }
        .header-table td {
            padding: 8px 10px;
            vertical-align: top;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #1a237e;
        }
        .company-details {
            font-size: 9px;
            color: #555;
            line-height: 1.5;
        }
        .invoice-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            padding: 6px 0;
            background: #e8eaf6;
            border: 1px solid #333;
            border-top: none;
            letter-spacing: 2px;
        }

        /* Invoice Meta */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            border-top: none;
        }
        .meta-table td {
            padding: 4px 10px;
            border: 1px solid #ccc;
            font-size: 9px;
        }
        .meta-label {
            font-weight: bold;
            color: #555;
            width: 20%;
        }
        .meta-value {
            width: 30%;
        }

        /* Party Info */
        .party-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            border-top: none;
        }
        .party-table td {
            padding: 8px 10px;
            vertical-align: top;
            width: 50%;
            border: 1px solid #ccc;
        }
        .party-title {
            font-size: 9px;
            font-weight: bold;
            color: #1a237e;
            text-transform: uppercase;
            margin-bottom: 5px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }
        .party-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .party-detail {
            font-size: 9px;
            color: #555;
            line-height: 1.6;
        }
        .party-detail strong {
            color: #333;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            border-top: none;
        }
        .items-table th {
            background: #1a237e;
            color: #fff;
            padding: 6px 5px;
            font-size: 8px;
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            border: 1px solid #1a237e;
        }
        .items-table td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        .items-table tr:nth-child(even) {
            background: #f5f5f5;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .text-center {
            text-align: center;
        }
        .items-table .item-name {
            font-weight: bold;
        }
        .items-table .item-code {
            font-size: 8px;
            color: #777;
        }

        /* HSN Summary */
        .hsn-table {
            width: 60%;
            border-collapse: collapse;
            border: 1px solid #333;
            border-top: none;
        }
        .hsn-table th {
            background: #e8eaf6;
            padding: 4px 5px;
            font-size: 8px;
            text-align: center;
            border: 1px solid #ccc;
        }
        .hsn-table td {
            padding: 4px 5px;
            font-size: 8px;
            text-align: center;
            border: 1px solid #ccc;
        }

        /* Totals */
        .totals-outer {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            border-top: none;
        }
        .totals-outer td {
            vertical-align: top;
        }
        .amount-words {
            padding: 8px 10px;
            font-size: 9px;
            border-right: 1px solid #ccc;
            width: 60%;
        }
        .amount-words-label {
            font-weight: bold;
            color: #555;
            font-size: 8px;
            text-transform: uppercase;
        }
        .amount-words-text {
            font-weight: bold;
            font-size: 10px;
            margin-top: 3px;
        }
        .totals-section {
            padding: 0;
            width: 40%;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 9px;
            border-bottom: 1px solid #eee;
        }
        .totals-table .label-col {
            text-align: right;
            color: #555;
            width: 60%;
        }
        .totals-table .value-col {
            text-align: right;
            font-weight: bold;
            width: 40%;
        }
        .totals-table .grand-total td {
            background: #1a237e;
            color: #fff;
            font-size: 11px;
            padding: 6px 8px;
            font-weight: bold;
        }

        /* Bank Details & Signature */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            border-top: none;
        }
        .footer-table td {
            padding: 10px;
            vertical-align: top;
            border: 1px solid #ccc;
        }
        .bank-title {
            font-weight: bold;
            font-size: 9px;
            color: #1a237e;
            margin-bottom: 5px;
        }
        .bank-detail {
            font-size: 8px;
            line-height: 1.6;
        }
        .signature-box {
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 9px;
            font-weight: bold;
        }
        .signature-sub {
            font-size: 8px;
            color: #666;
        }

        /* Terms */
        .terms-section {
            border: 1px solid #333;
            border-top: none;
            padding: 8px 10px;
            font-size: 8px;
            color: #666;
        }
        .terms-title {
            font-weight: bold;
            font-size: 8px;
            color: #333;
            margin-bottom: 3px;
        }

        /* Footer */
        .doc-footer {
            text-align: center;
            font-size: 8px;
            color: #999;
            margin-top: 8px;
        }

        /* Payment Status Badge */
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            color: #fff;
        }
        .status-draft { background: #9e9e9e; }
        .status-sent { background: #2196f3; }
        .status-paid { background: #4caf50; }
        .status-partially_paid { background: #ff9800; }
        .status-overdue { background: #f44336; }
        .status-cancelled { background: #795548; }
    </style>
</head>
<body>
<div class="container">

    <!-- Company Header -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div style="margin-bottom: 5px;">@include('pdfs.partials.logo', ['logoColor' => '#1a237e'])</div>
                <div class="company-details">
                    @if($company->address ?? null){{ $company->address }}@endif
                    @if($company->pincode ?? null), {{ $company->pincode }}@endif
                    <br>
                    @if($company->phone ?? null)Phone: {{ $company->phone }}@endif
                    @if($company->email ?? null) | Email: {{ $company->email }}@endif
                    @if($company->gstin ?? null)<br>GSTIN: {{ $company->gstin }}@endif
                </div>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle;">
                <span class="status-badge status-{{ $invoice->status }}">{{ strtoupper(str_replace('_', ' ', $invoice->status)) }}</span>
            </td>
        </tr>
    </table>

    <!-- Invoice Title -->
    <div class="invoice-title">TAX INVOICE</div>

    <!-- Invoice Meta Info -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Invoice No.</td>
            <td class="meta-value"><strong>{{ $invoice->invoice_number }}</strong></td>
            <td class="meta-label">Invoice Date</td>
            <td class="meta-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-M-Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Due Date</td>
            <td class="meta-value">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d-M-Y') : '-' }}</td>
            <td class="meta-label">Sales Order</td>
            <td class="meta-value">{{ $invoice->salesOrder->order_number ?? '-' }}</td>
        </tr>
    </table>

    <!-- Billed To / Ship To -->
    <table class="party-table">
        <tr>
            <td>
                <div class="party-title">Bill To</div>
                <div class="party-name">{{ $retailer->shop_name ?? $retailer->name ?? 'N/A' }}</div>
                <div class="party-detail">
                    @if($retailer->name && $retailer->shop_name)<strong>Contact:</strong> {{ $retailer->name }}<br>@endif
                    @if($retailer->address){{ $retailer->address }}@endif
                    @if($retailer->city), {{ $retailer->city }}@endif
                    @if($retailer->pincode) - {{ $retailer->pincode }}@endif
                    @if($retailer->phone)<br><strong>Phone:</strong> {{ $retailer->phone }}@endif
                    @if($retailer->email)<br><strong>Email:</strong> {{ $retailer->email }}@endif
                    @if($retailer->gst_no)<br><strong>GSTIN:</strong> {{ $retailer->gst_no }}@endif
                    @if($retailer->pan_no)<br><strong>PAN:</strong> {{ $retailer->pan_no }}@endif
                </div>
            </td>
            <td>
                <div class="party-title">Ship To</div>
                <div class="party-name">{{ $retailer->shop_name ?? $retailer->name ?? 'N/A' }}</div>
                <div class="party-detail">
                    @if($retailer->address){{ $retailer->address }}@endif
                    @if($retailer->city), {{ $retailer->city }}@endif
                    @if($retailer->pincode) - {{ $retailer->pincode }}@endif
                    @if($retailer->phone)<br><strong>Phone:</strong> {{ $retailer->phone }}@endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 32%;">Item Description</th>
                <th style="width: 8%;">HSN</th>
                <th style="width: 7%;">Qty</th>
                <th style="width: 10%;">Rate</th>
                <th style="width: 8%;">Disc.</th>
                <th style="width: 10%;">Taxable Amt</th>
                <th style="width: 6%;">Tax %</th>
                <th style="width: 7%;">CGST</th>
                <th style="width: 7%;">SGST</th>
                <th style="width: 11%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $totalTaxableAmount = 0; $totalCgst = 0; $totalSgst = 0; @endphp
            @foreach($invoice->items as $index => $item)
            @php
                $taxableAmount = $item->amount - ($item->discount_amount ?? 0);
                $halfTax = ($item->tax_amount ?? 0) / 2;
                $totalTaxableAmount += $taxableAmount;
                $totalCgst += $halfTax;
                $totalSgst += $halfTax;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <span class="item-name">{{ $item->sku->name ?? 'N/A' }}</span>
                    @if($item->sku->code ?? null)<br><span class="item-code">{{ $item->sku->code }}</span>@endif
                </td>
                <td class="text-center">{{ $item->sku->hsn_code ?? '-' }}</td>
                <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($item->rate, 2) }}</td>
                <td class="text-right">{{ number_format($item->discount_amount ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($taxableAmount, 2) }}</td>
                <td class="text-center">{{ number_format($item->tax_percentage ?? 0, 1) }}%</td>
                <td class="text-right">{{ number_format($halfTax, 2) }}</td>
                <td class="text-right">{{ number_format($halfTax, 2) }}</td>
                <td class="text-right"><strong>{{ number_format($taxableAmount + ($item->tax_amount ?? 0), 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #e8eaf6; font-weight: bold;">
                <td colspan="3" class="text-center">Total</td>
                <td class="text-center">{{ number_format($invoice->items->sum('quantity'), 2) }}</td>
                <td></td>
                <td class="text-right">{{ number_format($invoice->items->sum('discount_amount'), 2) }}</td>
                <td class="text-right">{{ number_format($totalTaxableAmount, 2) }}</td>
                <td></td>
                <td class="text-right">{{ number_format($totalCgst, 2) }}</td>
                <td class="text-right">{{ number_format($totalSgst, 2) }}</td>
                <td class="text-right"><strong>{{ number_format($invoice->total, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Amount in Words + Totals -->
    <table class="totals-outer">
        <tr>
            <td class="amount-words">
                <div class="amount-words-label">Amount in Words</div>
                <div class="amount-words-text">{{ $amountInWords }}</div>

                @if($invoice->notes)
                <div style="margin-top: 10px;">
                    <span class="amount-words-label">Notes:</span><br>
                    <span style="font-size: 9px; color: #555;">{{ $invoice->notes }}</span>
                </div>
                @endif
            </td>
            <td class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td class="label-col">Subtotal:</td>
                        <td class="value-col">{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td class="label-col">Discount:</td>
                        <td class="value-col">- {{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label-col">CGST:</td>
                        <td class="value-col">{{ number_format($totalCgst, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">SGST:</td>
                        <td class="value-col">{{ number_format($totalSgst, 2) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td class="label-col" style="text-align: right;">GRAND TOTAL:</td>
                        <td class="value-col" style="text-align: right;">&#8377; {{ number_format($invoice->total, 2) }}</td>
                    </tr>
                    @if($invoice->amount_paid > 0)
                    <tr>
                        <td class="label-col" style="color: #4caf50;">Amount Paid:</td>
                        <td class="value-col" style="color: #4caf50;">{{ number_format($invoice->amount_paid, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label-col" style="color: #f44336;">Balance Due:</td>
                        <td class="value-col" style="color: #f44336;">&#8377; {{ number_format($invoice->balance_due, 2) }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Bank Details & Signature -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                <div class="bank-title">Bank Details</div>
                <div class="bank-detail">
                    <strong>Bank Name:</strong> {{ $company->bank_name ?? '_______________' }}<br>
                    <strong>Account No:</strong> {{ $company->bank_account_no ?? '_______________' }}<br>
                    <strong>IFSC Code:</strong> {{ $company->bank_ifsc ?? '_______________' }}<br>
                    <strong>Branch:</strong> {{ $company->bank_branch ?? '_______________' }}
                </div>
            </td>
            <td style="width: 50%;">
                <div class="signature-box">
                    <div style="font-size: 9px; color: #555;">For {{ $company->name ?? 'Trumac' }}</div>
                    <div class="signature-line">Authorized Signatory</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Terms -->
    @if($invoice->terms)
    <div class="terms-section">
        <div class="terms-title">Terms & Conditions</div>
        {{ $invoice->terms }}
    </div>
    @endif

    <!-- Footer -->
    <div class="doc-footer">
        This is a computer-generated invoice. | Generated on {{ now()->format('d M Y, h:i A') }} | {{ config('app.name') }}
    </div>

</div>
</body>
</html>
