<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Challan - {{ $challan->challan_number }}</title>
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
            color: #0d47a1;
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
            background: #e3f2fd;
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
            color: #0d47a1;
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

        /* Transport Info */
        .transport-section {
            border: 1px solid #333;
            border-top: none;
            padding: 8px 10px;
            background: #e3f2fd;
        }
        .transport-title {
            font-weight: bold;
            font-size: 9px;
            color: #0d47a1;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .transport-grid {
            display: table;
            width: 100%;
        }
        .transport-item {
            display: table-cell;
            width: 33%;
            font-size: 9px;
        }
        .transport-item label {
            font-size: 8px;
            color: #666;
            display: block;
        }
        .transport-item span {
            font-weight: bold;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            border-top: none;
        }
        .items-table th {
            background: #0d47a1;
            color: #fff;
            padding: 6px 5px;
            font-size: 8px;
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            border: 1px solid #0d47a1;
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

        /* Summary */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            border-top: none;
        }
        .summary-table td {
            padding: 6px 10px;
            border: 1px solid #ccc;
            font-size: 9px;
            vertical-align: top;
        }

        /* Timeline */
        .timeline-section {
            border: 1px solid #333;
            border-top: none;
            padding: 8px 10px;
        }
        .timeline-title {
            font-weight: bold;
            font-size: 9px;
            color: #0d47a1;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .timeline-grid {
            display: table;
            width: 100%;
        }
        .timeline-item {
            display: table-cell;
            width: 33%;
            font-size: 9px;
        }
        .timeline-item label {
            font-size: 8px;
            color: #666;
            display: block;
        }
        .timeline-item span {
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
        .status-generated { background: #ff9800; }
        .status-in_transit { background: #2196f3; }
        .status-delivered { background: #4caf50; }
        .status-received { background: #00897b; }
        .status-cancelled { background: #f44336; }
    </style>
</head>
<body>
<div class="container">

    <!-- Company Header -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                @php $company = $challan->company; @endphp
                <div style="margin-bottom: 5px;">@include('pdfs.partials.logo', ['logoColor' => '#1a237e'])</div>
                <div class="company-details">
                    @if($challan->company->address ?? null){{ $challan->company->address }}@endif
                    @if($challan->company->pincode ?? null), {{ $challan->company->pincode }}@endif
                    <br>
                    @if($challan->company->phone ?? null)Phone: {{ $challan->company->phone }}@endif
                    @if($challan->company->email ?? null) | Email: {{ $challan->company->email }}@endif
                </div>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: middle;">
                <span class="status-badge status-{{ $challan->status }}">{{ strtoupper(str_replace('_', ' ', $challan->status)) }}</span>
            </td>
        </tr>
    </table>

    <!-- Title -->
    <div class="doc-title">DELIVERY CHALLAN</div>

    <!-- Meta Info -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Challan No.</td>
            <td class="meta-value"><strong>{{ $challan->challan_number }}</strong></td>
            <td class="meta-label">Challan Date</td>
            <td class="meta-value">{{ \Carbon\Carbon::parse($challan->created_at)->format('d-M-Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Requisition Ref.</td>
            <td class="meta-value">{{ $challan->requisition->requisition_number ?? '-' }}</td>
            <td class="meta-label">Vehicle No.</td>
            <td class="meta-value">{{ $challan->vehicle_number ?? '-' }}</td>
        </tr>
    </table>

    <!-- From / To -->
    <table class="party-table">
        <tr>
            <td>
                <div class="party-title">From (Dispatch Point)</div>
                <div class="party-name">
                    @if($challan->from_location_type === 'head_office')
                        Head Office
                    @else
                        {{ $challan->from_location->name ?? ucfirst(str_replace('_', ' ', $challan->from_location_type)) }}
                    @endif
                </div>
                <div class="party-detail">
                    @if($challan->from_location && isset($challan->from_location->location))
                        {{ $challan->from_location->location->address ?? '' }}
                        @if($challan->from_location->location->city ?? null)
                            <br>{{ $challan->from_location->location->city }}, {{ $challan->from_location->location->state ?? '' }}
                        @endif
                    @elseif($challan->from_location && ($challan->from_location->address ?? null))
                        {{ $challan->from_location->address }}
                        @if($challan->from_location->city ?? null), {{ $challan->from_location->city }}@endif
                    @endif
                    @if($challan->from_location && ($challan->from_location->phone ?? null))
                        <br><strong>Phone:</strong> {{ $challan->from_location->phone }}
                    @endif
                </div>
            </td>
            <td>
                <div class="party-title">To (Delivery Point)</div>
                <div class="party-name">{{ $challan->to_location->name ?? ucfirst(str_replace('_', ' ', $challan->to_location_type)) }}</div>
                <div class="party-detail">
                    @if($challan->to_location && isset($challan->to_location->location))
                        {{ $challan->to_location->location->address ?? '' }}
                        @if($challan->to_location->location->city ?? null)
                            <br>{{ $challan->to_location->location->city }}, {{ $challan->to_location->location->state ?? '' }}
                        @endif
                    @elseif($challan->to_location && ($challan->to_location->address ?? null))
                        {{ $challan->to_location->address }}
                        @if($challan->to_location->city ?? null), {{ $challan->to_location->city }}@endif
                    @endif
                    @if($challan->to_location && ($challan->to_location->phone ?? null))
                        <br><strong>Phone:</strong> {{ $challan->to_location->phone }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Transport Information -->
    @if($challan->driver || $challan->vehicle_number)
    <div class="transport-section">
        <div class="transport-title">Transport Information</div>
        <div class="transport-grid">
            @if($challan->driver)
            <div class="transport-item">
                <label>Driver Name</label>
                <span>{{ $challan->driver->first_name }} {{ $challan->driver->last_name }}</span>
            </div>
            <div class="transport-item">
                <label>Driver Phone</label>
                <span>{{ $challan->driver->phone ?? 'N/A' }}</span>
            </div>
            @endif
            @if($challan->vehicle_number)
            <div class="transport-item">
                <label>Vehicle Number</label>
                <span>{{ $challan->vehicle_number }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 12%;">SKU Code</th>
                <th style="width: 43%;">Item Description</th>
                <th style="width: 10%;">HSN</th>
                <th style="width: 15%;">Quantity</th>
                <th style="width: 15%;">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($challan->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center"><span class="item-code">{{ $item->sku->code ?? '-' }}</span></td>
                <td><span class="item-name">{{ $item->sku->name ?? 'N/A' }}</span></td>
                <td class="text-center">{{ $item->sku->hsn_code ?? '-' }}</td>
                <td class="text-center"><strong>{{ number_format($item->quantity, 2) }}</strong></td>
                <td class="text-center">{{ $item->sku->unit ?? 'unit' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #e3f2fd; font-weight: bold;">
                <td colspan="4" class="text-right">Total Items: {{ $challan->items->count() }}</td>
                <td class="text-center"><strong>{{ number_format($challan->items->sum('quantity'), 2) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <!-- Timeline -->
    @if($challan->dispatched_at || $challan->delivered_at)
    <div class="timeline-section">
        <div class="timeline-title">Delivery Timeline</div>
        <div class="timeline-grid">
            <div class="timeline-item">
                <label>Created</label>
                <span>{{ \Carbon\Carbon::parse($challan->created_at)->format('d M Y, h:i A') }}</span>
            </div>
            @if($challan->dispatched_at)
            <div class="timeline-item">
                <label>Dispatched At</label>
                <span>{{ \Carbon\Carbon::parse($challan->dispatched_at)->format('d M Y, h:i A') }}</span>
            </div>
            @endif
            @if($challan->delivered_at)
            <div class="timeline-item">
                <label>Delivered At</label>
                <span>{{ \Carbon\Carbon::parse($challan->delivered_at)->format('d M Y, h:i A') }}</span>
            </div>
            @endif
        </div>
        @if($challan->received_by)
        <div style="margin-top: 5px; font-size: 9px;">
            <strong>Received By:</strong> {{ $challan->received_by }}
        </div>
        @endif
    </div>
    @endif

    <!-- Notes -->
    @if($challan->notes)
    <div class="notes-section">
        <div class="notes-title">Notes</div>
        {{ $challan->notes }}
    </div>
    @endif

    @if($challan->delivery_notes)
    <div class="notes-section">
        <div class="notes-title">Delivery Notes</div>
        {{ $challan->delivery_notes }}
    </div>
    @endif

    <!-- Signature -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                <div class="signature-box">
                    <div class="signature-line">Prepared By / Dispatch Signature</div>
                </div>
            </td>
            <td style="width: 50%;">
                <div class="signature-box">
                    <div class="signature-line">Receiver Signature & Date</div>
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
