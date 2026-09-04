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
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
            margin-bottom: 5px;
        }
        .company-address {
            font-size: 11px;
            color: #666;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
            background: #f5f5f5;
            padding: 10px;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        .info-box.right {
            text-align: right;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .info-value {
            font-size: 12px;
            margin-bottom: 8px;
        }
        .location-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 15px;
            background: #fafafa;
        }
        .location-title {
            font-weight: bold;
            font-size: 11px;
            color: #1976d2;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.items th {
            background: #1976d2;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        table.items td {
            padding: 10px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
        }
        table.items tr:nth-child(even) {
            background: #f9f9f9;
        }
        table.items .text-right {
            text-align: right;
        }
        table.items .text-center {
            text-align: center;
        }
        .totals {
            float: right;
            width: 250px;
            margin-top: 10px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 5px 10px;
            font-size: 12px;
        }
        .totals .label {
            text-align: right;
            color: #666;
        }
        .totals .value {
            text-align: right;
            font-weight: bold;
        }
        .totals .grand-total {
            background: #1976d2;
            color: white;
        }
        .totals .grand-total td {
            padding: 10px;
            font-size: 14px;
        }
        .clearfix {
            clear: both;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .signature-section {
            margin-top: 50px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 10px;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div style="margin-bottom: 5px;">@include('pdfs.partials.logo', ['logoColor' => '#1a237e'])</div>
            <div class="company-address">
                {{ $company->address ?? '' }}
                @if($company->phone ?? null) | Phone: {{ $company->phone }} @endif
                @if($company->email ?? null) | Email: {{ $company->email }} @endif
            </div>
        </div>

        <!-- Invoice Title -->
        <div class="invoice-title">PURCHASE INVOICE</div>

        <!-- Invoice Info -->
        <div class="info-section">
            <div class="info-box">
                <div class="info-label">Invoice Number</div>
                <div class="info-value"><strong>{{ $invoice->invoice_number }}</strong></div>

                <div class="info-label">Invoice Date</div>
                <div class="info-value">{{ $invoice->invoice_date->format('d M, Y') }}</div>

                @if($invoice->requisition)
                <div class="info-label">Requisition Number</div>
                <div class="info-value">{{ $invoice->requisition->requisition_number }}</div>
                @endif
            </div>
            <div class="info-box right">
                <div class="info-label">GRN Number</div>
                <div class="info-value">{{ $invoice->grn->grn_number ?? '-' }}</div>

                <div class="info-label">Status</div>
                <div class="info-value">{{ ucfirst($invoice->status) }}</div>
            </div>
        </div>

        <!-- From/To Locations -->
        <div class="info-section">
            <div class="info-box" style="padding-right: 10px;">
                <div class="location-box">
                    <div class="location-title">From (Supplier)</div>
                    <div class="info-value">
                        <strong>{{ $fromLocation->name ?? 'N/A' }}</strong><br>
                        {{ $fromLocation->address ?? '' }}<br>
                        @if($fromLocation->phone ?? null) Phone: {{ $fromLocation->phone }} @endif
                    </div>
                </div>
            </div>
            <div class="info-box" style="padding-left: 10px;">
                <div class="location-box">
                    <div class="location-title">To (Receiver)</div>
                    <div class="info-value">
                        <strong>{{ $toLocation->name ?? 'N/A' }}</strong><br>
                        {{ $toLocation->address ?? '' }}<br>
                        @if($toLocation->phone ?? null) Phone: {{ $toLocation->phone }} @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Item Description</th>
                    <th style="width: 10%;" class="text-center">Qty</th>
                    <th style="width: 15%;" class="text-right">Unit Price</th>
                    <th style="width: 10%;" class="text-center">Tax %</th>
                    <th style="width: 12%;" class="text-right">Tax Amt</th>
                    <th style="width: 13%;" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->sku->name ?? 'N/A' }}</strong><br>
                        <span style="color: #666; font-size: 10px;">Code: {{ $item->sku->code ?? '-' }}</span>
                        @if($item->batch_number)
                        <br><span style="color: #666; font-size: 10px;">Batch: {{ $item->batch_number }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 2) }} {{ $item->sku->unit ?? '' }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-center">{{ number_format($item->tax_percent, 2) }}%</td>
                    <td class="text-right">{{ number_format($item->tax_amount, 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($item->total, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="value">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Tax Amount:</td>
                    <td class="value">{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr class="grand-total">
                    <td class="label">Grand Total:</td>
                    <td class="value">{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="clearfix"></div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Prepared By</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Received By</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is a computer-generated invoice and does not require a signature.</p>
            <p>Generated on {{ now()->format('d M, Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
