<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Order - {{ $salesOrder->order_number }}</title>
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

        /* Meta */
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

        .notes-section {
            border: 1px solid #333;
            border-top: none;
            padding: 8px 10px;
            font-size: 8px;
            color: #666;
        }
        .notes-title {
            font-weight: bold;
            font-size: 8px;
            color: #333;
            margin-bottom: 3px;
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
        .status-draft { background: #9e9e9e; }
        .status-pending { background: #ff9800; }
        .status-approved { background: #2196f3; }
        .status-processing { background: #673ab7; }
        .status-dispatched { background: #9c27b0; }
        .status-delivered { background: #4caf50; }
        .status-cancelled { background: #f44336; }
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
                <span class="status-badge status-{{ $salesOrder->status }}">{{ strtoupper(str_replace('_', ' ', $salesOrder->status)) }}</span>
            </td>
        </tr>
    </table>

    <!-- Title -->
    <div class="invoice-title">SALES ORDER</div>

    <!-- Meta Info -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Order No.</td>
            <td class="meta-value"><strong>{{ $salesOrder->order_number }}</strong></td>
            <td class="meta-label">Order Date</td>
            <td class="meta-value">{{ \Carbon\Carbon::parse($salesOrder->order_date)->format('d-M-Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Expected Delivery</td>
            <td class="meta-value">{{ $salesOrder->expected_delivery_date ? \Carbon\Carbon::parse($salesOrder->expected_delivery_date)->format('d-M-Y') : '-' }}</td>
            <td class="meta-label">Created By</td>
            <td class="meta-value">{{ ($salesOrder->createdBy->first_name ?? '') . ' ' . ($salesOrder->createdBy->last_name ?? '') }}</td>
        </tr>
    </table>

    <!-- Bill To / Ship To -->
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
                <th style="width: 8%;">Qty</th>
                <th style="width: 11%;">Rate</th>
                <th style="width: 8%;">Disc %</th>
                <th style="width: 8%;">Disc Amt</th>
                <th style="width: 7%;">Tax %</th>
                <th style="width: 7%;">Tax Amt</th>
                <th style="width: 12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCgst = 0; $totalSgst = 0; @endphp
            @foreach($salesOrder->items as $index => $item)
            @php
                $halfTax = ($item->tax_amount ?? 0) / 2;
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
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-center">{{ number_format($item->discount_percent ?? 0, 1) }}%</td>
                <td class="text-right">{{ number_format($item->discount_amount ?? 0, 2) }}</td>
                <td class="text-center">{{ number_format($item->tax_percent ?? 0, 1) }}%</td>
                <td class="text-right">{{ number_format($item->tax_amount ?? 0, 2) }}</td>
                <td class="text-right"><strong>{{ number_format($item->total, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #e8eaf6; font-weight: bold;">
                <td colspan="3" class="text-center">Total</td>
                <td class="text-center">{{ number_format($salesOrder->items->sum('quantity'), 2) }}</td>
                <td></td>
                <td></td>
                <td class="text-right">{{ number_format($salesOrder->items->sum('discount_amount'), 2) }}</td>
                <td></td>
                <td class="text-right">{{ number_format($salesOrder->tax_amount, 2) }}</td>
                <td class="text-right"><strong>{{ number_format($salesOrder->total_amount, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Amount in Words + Totals -->
    <table class="totals-outer">
        <tr>
            <td class="amount-words">
                <div class="amount-words-label">Amount in Words</div>
                <div class="amount-words-text">{{ $amountInWords }}</div>
            </td>
            <td class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td class="label-col">Subtotal:</td>
                        <td class="value-col">{{ number_format($salesOrder->subtotal, 2) }}</td>
                    </tr>
                    @if($salesOrder->discount_amount > 0)
                    <tr>
                        <td class="label-col">Discount:</td>
                        <td class="value-col">- {{ number_format($salesOrder->discount_amount, 2) }}</td>
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
                        <td class="value-col" style="text-align: right;">&#8377; {{ number_format($salesOrder->total_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Notes -->
    @if($salesOrder->notes)
    <div class="notes-section">
        <div class="notes-title">Notes</div>
        {{ $salesOrder->notes }}
    </div>
    @endif

    <!-- Signature -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-box">
                    <div class="signature-line">Customer's Signature</div>
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

    <!-- Footer -->
    <div class="doc-footer">
        This is a computer-generated document. | Generated on {{ now()->format('d M Y, h:i A') }} | {{ config('app.name') }}
    </div>

</div>
</body>
</html>
