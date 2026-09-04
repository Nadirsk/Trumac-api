<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Invoice - {{ $invoice->invoice_number }}</title>
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
            color: #1b5e20;
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
            background: #e8f5e9;
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
            color: #1b5e20;
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
            background: #1b5e20;
            color: #fff;
            padding: 6px 5px;
            font-size: 8px;
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            border: 1px solid #1b5e20;
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
            background: #1b5e20;
            color: #fff;
            font-size: 11px;
            padding: 6px 8px;
            font-weight: bold;
        }

        /* Signature */
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

        .doc-footer {
            text-align: center;
            font-size: 8px;
            color: #999;
            margin-top: 8px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            color: #fff;
        }
        .status-generated { background: #ff9800; }
        .status-sent { background: #2196f3; }
        .status-paid { background: #4caf50; }
    </style>
</head>
<body>
<div class="container">

    <!-- Company Header -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div style="margin-bottom: 5px;">@include('pdfs.partials.logo', ['logoColor' => '#1b5e20'])</div>
                <div class="company-details">
                    @if($company->address ?? null){{ $company->address }}@endif
                    @if($company->pincode ?? null), {{ $company->pincode }}@endif
                    <br>
                    @if($company->phone ?? null)Phone: {{ $company->phone }}@endif
                    @if($company->email ?? null) | Email: {{ $company->email }}@endif
                </div>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle;">
                <span class="status-badge status-{{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span>
            </td>
        </tr>
    </table>

    <!-- Invoice Title -->
    <div class="invoice-title">PURCHASE INVOICE</div>

    <!-- Invoice Meta Info -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Invoice No.</td>
            <td class="meta-value"><strong>{{ $invoice->invoice_number }}</strong></td>
            <td class="meta-label">Invoice Date</td>
            <td class="meta-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-M-Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">GRN No.</td>
            <td class="meta-value">{{ $invoice->grn->grn_number ?? '-' }}</td>
            <td class="meta-label">Requisition No.</td>
            <td class="meta-value">{{ $invoice->requisition->requisition_number ?? '-' }}</td>
        </tr>
    </table>

    <!-- From / To -->
    <table class="party-table">
        <tr>
            <td>
                <div class="party-title">From (Supplier)</div>
                <div class="party-name">{{ $fromLocation->name ?? ucfirst(str_replace('_', ' ', $invoice->from_location_type ?? 'N/A')) }}</div>
                <div class="party-detail">
                    @if($fromLocation->address ?? null){{ $fromLocation->address }}@endif
                    @if($fromLocation->city ?? null), {{ $fromLocation->city }}@endif
                    @if($fromLocation->pincode ?? null) - {{ $fromLocation->pincode }}@endif
                    @if($fromLocation->phone ?? null)<br><strong>Phone:</strong> {{ $fromLocation->phone }}@endif
                    @if($fromLocation->gst_no ?? null)<br><strong>GSTIN:</strong> {{ $fromLocation->gst_no }}@endif
                </div>
            </td>
            <td>
                <div class="party-title">To (Receiver)</div>
                <div class="party-name">{{ $toLocation->name ?? ucfirst(str_replace('_', ' ', $invoice->to_location_type ?? 'N/A')) }}</div>
                <div class="party-detail">
                    @if($toLocation->address ?? null){{ $toLocation->address }}@endif
                    @if($toLocation->city ?? null), {{ $toLocation->city }}@endif
                    @if($toLocation->pincode ?? null) - {{ $toLocation->pincode }}@endif
                    @if($toLocation->phone ?? null)<br><strong>Phone:</strong> {{ $toLocation->phone }}@endif
                    @if($toLocation->gst_no ?? null)<br><strong>GSTIN:</strong> {{ $toLocation->gst_no }}@endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 30%;">Item Description</th>
                <th style="width: 8%;">HSN</th>
                <th style="width: 10%;">Batch</th>
                <th style="width: 8%;">Qty</th>
                <th style="width: 11%;">Unit Price</th>
                <th style="width: 7%;">Tax %</th>
                <th style="width: 10%;">Tax Amt</th>
                <th style="width: 12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $totalTaxable = 0; $totalTaxAmt = 0; @endphp
            @foreach($items as $index => $item)
            @php
                $lineAmount = $item->quantity * $item->unit_price;
                $totalTaxable += $lineAmount;
                $totalTaxAmt += $item->tax_amount;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <span class="item-name">{{ $item->sku->name ?? 'N/A' }}</span>
                    @if($item->sku->code ?? null)<br><span class="item-code">{{ $item->sku->code }}</span>@endif
                </td>
                <td class="text-center">{{ $item->sku->hsn_code ?? '-' }}</td>
                <td class="text-center">{{ $item->batch_number ?? '-' }}</td>
                <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-center">{{ number_format($item->tax_percent, 1) }}%</td>
                <td class="text-right">{{ number_format($item->tax_amount, 2) }}</td>
                <td class="text-right"><strong>{{ number_format($item->total, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #e8f5e9; font-weight: bold;">
                <td colspan="4" class="text-center">Total</td>
                <td class="text-center">{{ number_format($items->sum('quantity'), 2) }}</td>
                <td></td>
                <td></td>
                <td class="text-right">{{ number_format($totalTaxAmt, 2) }}</td>
                <td class="text-right"><strong>{{ number_format($invoice->total_amount, 2) }}</strong></td>
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
                    <tr>
                        <td class="label-col">CGST:</td>
                        <td class="value-col">{{ number_format($invoice->tax_amount / 2, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">SGST:</td>
                        <td class="value-col">{{ number_format($invoice->tax_amount / 2, 2) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td class="label-col" style="text-align: right;">GRAND TOTAL:</td>
                        <td class="value-col" style="text-align: right;">&#8377; {{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Signature -->
    <table class="footer-table">
        <tr>
            <td style="width: 33%;">
                <div class="signature-box">
                    <div class="signature-line">Prepared By</div>
                </div>
            </td>
            <td style="width: 34%;">
                <div class="signature-box">
                    <div class="signature-line">Received By</div>
                </div>
            </td>
            <td style="width: 33%;">
                <div class="signature-box">
                    <div style="font-size: 9px; color: #555;">For {{ $company->name ?? 'Trumac' }}</div>
                    <div class="signature-line">Authorized Signatory</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="doc-footer">
        This is a computer-generated invoice. | Generated on {{ now()->format('d M Y, h:i A') }} | {{ config('app.name') }}
    </div>

</div>
</body>
</html>
