<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
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
            color: #2e7d32;
        }
        .company-details {
            font-size: 9px;
            color: #555;
            line-height: 1.5;
        }
        .doc-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            padding: 6px 0;
            background: #e8f5e9;
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
            color: #2e7d32;
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
            background: #2e7d32;
            color: #fff;
            padding: 6px 5px;
            font-size: 8px;
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            border: 1px solid #2e7d32;
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
            background: #2e7d32;
            color: #fff;
            font-size: 11px;
            padding: 6px 8px;
            font-weight: bold;
        }

        /* Notes */
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
        .status-draft { background: #9e9e9e; }
        .status-pending { background: #ff9800; }
        .status-approved { background: #2196f3; }
        .status-partial { background: #ff9800; }
        .status-received { background: #4caf50; }
        .status-cancelled { background: #f44336; }
    </style>
</head>
<body>
<div class="container">

    <!-- Company Header -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                @php $company = $purchaseOrder->company; @endphp
                <div style="margin-bottom: 5px;">@include('pdfs.partials.logo', ['logoColor' => '#2e7d32'])</div>
                <div class="company-details">
                    @if($purchaseOrder->company->address ?? null){{ $purchaseOrder->company->address }}@endif
                    @if($purchaseOrder->company->pincode ?? null), {{ $purchaseOrder->company->pincode }}@endif
                    <br>
                    @if($purchaseOrder->company->phone ?? null)Phone: {{ $purchaseOrder->company->phone }}@endif
                    @if($purchaseOrder->company->email ?? null) | Email: {{ $purchaseOrder->company->email }}@endif
                </div>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle;">
                <span class="status-badge status-{{ $purchaseOrder->status }}">{{ strtoupper($purchaseOrder->status) }}</span>
            </td>
        </tr>
    </table>

    <!-- Title -->
    <div class="doc-title">PURCHASE ORDER</div>

    <!-- Meta Info -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">PO No.</td>
            <td class="meta-value"><strong>{{ $purchaseOrder->po_number }}</strong></td>
            <td class="meta-label">Order Date</td>
            <td class="meta-value">{{ \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d-M-Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Expected Date</td>
            <td class="meta-value">{{ $purchaseOrder->expected_date ? \Carbon\Carbon::parse($purchaseOrder->expected_date)->format('d-M-Y') : '-' }}</td>
            <td class="meta-label">Requisition Ref.</td>
            <td class="meta-value">{{ $purchaseOrder->requisition->requisition_number ?? '-' }}</td>
        </tr>
    </table>

    <!-- Vendor / Delivery To -->
    <table class="party-table">
        <tr>
            <td>
                <div class="party-title">Vendor (Supplier)</div>
                <div class="party-name">{{ $purchaseOrder->vendor->name ?? 'N/A' }}</div>
                <div class="party-detail">
                    @if($purchaseOrder->vendor->address ?? null){{ $purchaseOrder->vendor->address }}@endif
                    @if($purchaseOrder->vendor->city ?? null), {{ $purchaseOrder->vendor->city }}@endif
                    @if($purchaseOrder->vendor->state ?? null), {{ $purchaseOrder->vendor->state }}@endif
                    @if($purchaseOrder->vendor->pincode ?? null) - {{ $purchaseOrder->vendor->pincode }}@endif
                    @if($purchaseOrder->vendor->phone ?? null)<br><strong>Phone:</strong> {{ $purchaseOrder->vendor->phone }}@endif
                    @if($purchaseOrder->vendor->email ?? null)<br><strong>Email:</strong> {{ $purchaseOrder->vendor->email }}@endif
                    @if($purchaseOrder->vendor->gst_no ?? null)<br><strong>GSTIN:</strong> {{ $purchaseOrder->vendor->gst_no }}@endif
                </div>
            </td>
            <td>
                <div class="party-title">Deliver To</div>
                <div class="party-name">{{ $purchaseOrder->destination->name ?? ucfirst(str_replace('_', ' ', $purchaseOrder->destination_type ?? 'Head Office')) }}</div>
                <div class="party-detail">
                    @if($purchaseOrder->destination && ($purchaseOrder->destination->address ?? null))
                        {{ $purchaseOrder->destination->address }}
                        @if($purchaseOrder->destination->city ?? null), {{ $purchaseOrder->destination->city }}@endif
                        @if($purchaseOrder->destination->pincode ?? null) - {{ $purchaseOrder->destination->pincode }}@endif
                        @if($purchaseOrder->destination->state ?? null), {{ $purchaseOrder->destination->state }}@endif
                    @elseif(!$purchaseOrder->destination && $purchaseOrder->company)
                        {{ $purchaseOrder->company->address ?? '' }}
                        @if($purchaseOrder->company->pincode ?? null), {{ $purchaseOrder->company->pincode }}@endif
                    @endif
                    @if($purchaseOrder->destination && ($purchaseOrder->destination->phone ?? null))
                        <br><strong>Phone:</strong> {{ $purchaseOrder->destination->phone }}
                    @endif
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
                <th style="width: 12%;">Unit Price</th>
                <th style="width: 7%;">Tax %</th>
                <th style="width: 10%;">Tax Amt</th>
                <th style="width: 7%;">Rcvd</th>
                <th style="width: 12%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $totalCgst = 0; $totalSgst = 0; @endphp
            @foreach($purchaseOrder->items as $index => $item)
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
                <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-center">{{ number_format($item->tax_percent, 0) }}%</td>
                <td class="text-right">{{ number_format($item->tax_amount ?? 0, 2) }}</td>
                <td class="text-center">{{ number_format($item->received_quantity ?? 0, 0) }}</td>
                <td class="text-right"><strong>{{ number_format($item->total, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #e8f5e9; font-weight: bold;">
                <td colspan="3" class="text-center">Total</td>
                <td class="text-center">{{ number_format($purchaseOrder->items->sum('quantity'), 0) }}</td>
                <td></td>
                <td></td>
                <td class="text-right">{{ number_format($purchaseOrder->tax_amount, 2) }}</td>
                <td class="text-center">{{ number_format($purchaseOrder->items->sum('received_quantity'), 0) }}</td>
                <td class="text-right"><strong>{{ number_format($purchaseOrder->total_amount, 2) }}</strong></td>
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
                        <td class="value-col">{{ number_format($purchaseOrder->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">CGST:</td>
                        <td class="value-col">{{ number_format($totalCgst, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label-col">SGST:</td>
                        <td class="value-col">{{ number_format($totalSgst, 2) }}</td>
                    </tr>
                    @if($purchaseOrder->discount_amount > 0)
                    <tr>
                        <td class="label-col">Discount:</td>
                        <td class="value-col">- {{ number_format($purchaseOrder->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="grand-total">
                        <td class="label-col" style="text-align: right;">GRAND TOTAL:</td>
                        <td class="value-col" style="text-align: right;">&#8377; {{ number_format($purchaseOrder->total_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Notes & Terms -->
    @if($purchaseOrder->notes)
    <div class="notes-section">
        <div class="notes-title">Notes</div>
        {{ $purchaseOrder->notes }}
    </div>
    @endif

    @if($purchaseOrder->terms)
    <div class="notes-section">
        <div class="notes-title">Terms & Conditions</div>
        {{ $purchaseOrder->terms }}
    </div>
    @endif

    <!-- Signature -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-box">
                    <div class="signature-line">Prepared By</div>
                </div>
            </td>
            <td style="width: 50%;">
                <div class="signature-box">
                    <div style="font-size: 9px; color: #555;">For {{ $purchaseOrder->company->name ?? 'Trumac' }}</div>
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
