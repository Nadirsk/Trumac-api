<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #4CAF50;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
            font-size: 28px;
        }
        .po-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        .po-info table {
            width: 100%;
        }
        .po-info td {
            padding: 8px 0;
        }
        .po-info td:first-child {
            font-weight: bold;
            width: 40%;
            color: #555;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }
        .items-table th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        .items-table tr:hover {
            background-color: #f5f5f5;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            margin-top: 30px;
            float: right;
            width: 300px;
        }
        .totals table {
            width: 100%;
            border-top: 2px solid #ddd;
            padding-top: 10px;
        }
        .totals td {
            padding: 8px 0;
        }
        .totals .total-row {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
            border-top: 2px solid #4CAF50;
        }
        .footer {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            color: #777;
            font-size: 14px;
        }
        .notes {
            background-color: #fff9e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .notes h3 {
            margin-top: 0;
            color: #f57c00;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                padding: 15px;
            }
            .items-table {
                font-size: 12px;
            }
            .totals {
                float: none;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>PURCHASE ORDER</h1>
            <p style="margin: 5px 0; color: #666;">{{ $purchaseOrder->po_number }}</p>
        </div>

        <!-- PO Information -->
        <div class="po-info">
            <table>
                <tr>
                    <td>Vendor:</td>
                    <td><strong>{{ $purchaseOrder->vendor->name ?? 'N/A' }}</strong></td>
                </tr>
                @if($purchaseOrder->vendor->phone)
                <tr>
                    <td>Contact:</td>
                    <td>{{ $purchaseOrder->vendor->phone }}</td>
                </tr>
                @endif
                @if($purchaseOrder->vendor->email)
                <tr>
                    <td>Email:</td>
                    <td>{{ $purchaseOrder->vendor->email }}</td>
                </tr>
                @endif
                <tr>
                    <td>Order Date:</td>
                    <td>{{ \Carbon\Carbon::parse($purchaseOrder->order_date)->format('d M Y') }}</td>
                </tr>
                @if($purchaseOrder->expected_date)
                <tr>
                    <td>Expected Delivery:</td>
                    <td>{{ \Carbon\Carbon::parse($purchaseOrder->expected_date)->format('d M Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td>Status:</td>
                    <td><span style="color: #4CAF50; font-weight: bold;">{{ strtoupper($purchaseOrder->status) }}</span></td>
                </tr>
            </table>
        </div>

        <!-- Delivery Address -->
        <div class="po-info" style="background-color: #e8f5e9; border-left: 4px solid #4CAF50;">
            <h3 style="margin: 0 0 10px 0; color: #2e7d32; font-size: 16px;">📍 Delivery Address</h3>
            <table>
                <tr>
                    <td style="width: 30%;">Deliver To:</td>
                    <td><strong>{{ $purchaseOrder->destination->name ?? ucfirst(str_replace('_', ' ', $purchaseOrder->destination_type ?? 'Head Office')) }}</strong></td>
                </tr>
                @if($purchaseOrder->destination && $purchaseOrder->destination->address)
                <tr>
                    <td>Address:</td>
                    <td>{{ $purchaseOrder->destination->address }}</td>
                </tr>
                @endif
                @if($purchaseOrder->destination && $purchaseOrder->destination->city)
                <tr>
                    <td>City:</td>
                    <td>{{ $purchaseOrder->destination->city }}@if($purchaseOrder->destination->pincode), {{ $purchaseOrder->destination->pincode }}@endif</td>
                </tr>
                @endif
                @if($purchaseOrder->destination && $purchaseOrder->destination->state)
                <tr>
                    <td>State:</td>
                    <td>{{ $purchaseOrder->destination->state }}</td>
                </tr>
                @endif
                @if($purchaseOrder->destination && $purchaseOrder->destination->phone)
                <tr>
                    <td>Contact Number:</td>
                    <td>{{ $purchaseOrder->destination->phone }}</td>
                </tr>
                @endif
                @if(!$purchaseOrder->destination && $purchaseOrder->company)
                <tr>
                    <td>Address:</td>
                    <td>
                        {{ $purchaseOrder->company->address ?? 'N/A' }}<br>
                        @if($purchaseOrder->company->city){{ $purchaseOrder->company->city }}@endif
                        @if($purchaseOrder->company->pincode), {{ $purchaseOrder->company->pincode }}@endif<br>
                        @if($purchaseOrder->company->state){{ $purchaseOrder->company->state }}@endif
                    </td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Items Table -->
        <h3 style="color: #333; margin-bottom: 15px;">Order Items</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="text-align: center; width: 5%;">#</th>
                    <th style="text-align: left; width: 35%;">Item</th>
                    <th style="text-align: center; width: 12%;">Quantity</th>
                    <th style="text-align: right; width: 16%;">Unit Price</th>
                    <th style="text-align: right; width: 12%;">Tax %</th>
                    <th style="text-align: right; width: 20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $index => $item)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td style="text-align: left;">
                        <strong>{{ $item->sku->name ?? 'N/A' }}</strong>
                        @if($item->sku->code)
                        <br><small style="color: #777;">Code: {{ $item->sku->code }}</small>
                        @endif
                    </td>
                    <td style="text-align: center;">{{ number_format($item->quantity, 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right;">{{ number_format($item->tax_percent, 2) }}%</td>
                    <td style="text-align: right;"><strong>₹{{ number_format($item->total, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td style="text-align: left;">Subtotal:</td>
                    <td style="text-align: right;">₹{{ number_format($purchaseOrder->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: left;">Tax:</td>
                    <td style="text-align: right;">₹{{ number_format($purchaseOrder->tax_amount, 2) }}</td>
                </tr>
                @if($purchaseOrder->discount_amount > 0)
                <tr>
                    <td style="text-align: left;">Discount:</td>
                    <td style="text-align: right;">- ₹{{ number_format($purchaseOrder->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td style="text-align: left;">Total Amount:</td>
                    <td style="text-align: right;">₹{{ number_format($purchaseOrder->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>

        <!-- Notes -->
        @if($purchaseOrder->notes)
        <div class="notes">
            <h3>Notes:</h3>
            <p style="margin: 0;">{{ $purchaseOrder->notes }}</p>
        </div>
        @endif

        @if($purchaseOrder->terms)
        <div class="notes">
            <h3>Terms & Conditions:</h3>
            <p style="margin: 0;">{{ $purchaseOrder->terms }}</p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>If you have any questions, please contact our procurement team.</p>
            <p style="margin-top: 15px; color: #999; font-size: 12px;">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
