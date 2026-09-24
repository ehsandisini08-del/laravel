<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111827;
        }
        .invoice {
            page-break-after: always;
            padding: 20px 0;
        }
        .invoice:last-child { page-break-after: auto; }

        .header { width: 100%; border-bottom: 3px solid #1d4ed8; padding-bottom: 10px; }
        .logo {
            width: 48px; height: 48px;
            background: #1d4ed8; color: #fff;
            font-size: 22px; font-weight: 800;
            text-align: center; line-height: 48px;
            border-radius: 8px;
        }
        .company-name { font-size: 18px; font-weight: 800; color: #111827; }
        .company-meta { font-size: 9px; color: #6b7280; line-height: 1.5; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { margin: 0; font-size: 28px; letter-spacing: 2px; color: #1d4ed8; font-weight: 800; }
        .invoice-title .num { font-size: 11px; color: #4b5563; }

        .stamp {
            display: inline-block;
            border: 2px solid;
            border-radius: 6px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
            margin-top: 4px;
        }
        .stamp-success { border-color: #059669; color: #059669; }
        .stamp-danger { border-color: #dc2626; color: #dc2626; }
        .stamp-default { border-color: #6b7280; color: #6b7280; }

        .columns { width: 100%; margin-top: 12px; }
        .columns td { vertical-align: top; }

        .panel { border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; }
        .panel-title { background: #1d4ed8; color: #fff; padding: 5px 8px; font-size: 9px; font-weight: 700; }
        .panel-body { padding: 8px; font-size: 10px; line-height: 1.6; }
        .panel-body .name { font-size: 12px; font-weight: 700; }
        .muted { color: #6b7280; }

        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; }
        .meta-table td.label { width: 40%; background: #f9fafb; font-weight: 600; color: #6b7280; }
        .meta-table td.value strong { color: #1d4ed8; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 10px; }
        .items-table thead th {
            text-align: left;
            padding: 7px 8px;
            background: #1d4ed8;
            color: #fff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table th.num, .items-table td.num { text-align: right; }
        .items-table th.center, .items-table td.center { text-align: center; }
        .items-table tbody td { padding: 7px 8px; border-bottom: 1px solid #f3f4f6; }
        .items-table .total-row td { padding: 8px; font-weight: 800; background: #eff6ff; border-bottom: none; }
        .total-amount { font-size: 14px; color: #1d4ed8; }

        .terbilang {
            margin-top: 12px;
            padding: 8px;
            border: 1px dashed #9ca3af;
            border-radius: 6px;
            font-size: 9px;
            font-style: italic;
            color: #374151;
            background: #f9fafb;
        }
        .terbilang strong { font-style: normal; }

        .notes { margin-top: 12px; font-size: 9px; color: #6b7280; line-height: 1.6; }
        .notes strong { color: #111827; }

        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { text-align: center; width: 50%; }
        .signatures .label { font-size: 9px; color: #6b7280; }
        .signatures .line { border-top: 1px solid #111827; margin-top: 60px; padding-top: 4px; font-size: 10px; font-weight: 600; }

        .footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>
    @php
        $companyName = $company['company_name'] ?: (\App\Models\Setting::get('app_name') ?: config('app.name', 'Perusahaan'));
    @endphp

    @foreach($invoices as $invoice)
        @php
            $stampText = match ($invoice->status->value) {
                'paid' => 'LUNAS',
                'unpaid' => 'BELUM BAYAR',
                'overdue' => 'TELAT BAYAR',
                'cancelled' => 'DIBATALKAN',
                default => 'DRAF',
            };
            $stampClass = match ($invoice->status->value) {
                'paid' => 'stamp-success',
                'unpaid', 'overdue' => 'stamp-danger',
                default => 'stamp-default',
            };
        @endphp

        <div class="invoice">
            <table class="header">
                <tr>
                    <td style="width: 60%;">
                        <table>
                            <tr>
                                <td style="vertical-align: middle;">
                                    @if(! empty($company['company_logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($company['company_logo']))
                                        <img src="{{ 'storage/'.$company['company_logo'] }}" alt="logo" style="max-height: 44px; max-width: 160px; margin-bottom: 4px;">
                                    @else
                                        <div class="logo">{{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}</div>
                                    @endif
                                </td>
                                <td style="padding-left: 10px; vertical-align: middle;">
                                    <div class="company-name">{{ $companyName }}</div>
                                    <div class="company-meta">
                                        @if(! empty($company['company_address'])) <div>{{ $company['company_address'] }}</div> @endif
                                        <div>
                                            @if(! empty($company['company_phone'])) <span>Telp: {{ $company['company_phone'] }}</span> @endif
                                            @if(! empty($company['company_email'])) <span>@if(! empty($company['company_phone'])) | @endif Email: {{ $company['company_email'] }}</span> @endif
                                        </div>
                                        <div>
                                            @if(! empty($company['company_tax_number'])) <span>NPWP: {{ $company['company_tax_number'] }}</span> @endif
                                            @if(! empty($company['company_website'])) <span>@if(! empty($company['company_tax_number'])) | @endif {{ $company['company_website'] }}</span> @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 40%;" class="invoice-title">
                        <h1>INVOICE</h1>
                        <div class="num">{{ $invoice->invoice_number }}</div>
                        @if(! in_array($invoice->status->value, ['draft', 'cancelled']))
                            <div class="stamp {{ $stampClass }}">{{ $stampText }}</div>
                        @endif
                    </td>
                </tr>
            </table>

            <table class="columns">
                <tr>
                    <td style="width: 50%; padding-right: 6px;">
                        <div class="panel">
                            <table class="meta-table">
                                <tr><td class="label">Tanggal Terbit</td><td class="value">{{ $invoice->created_at?->format('d M Y') }}</td></tr>
                                <tr><td class="label">Periode Tagihan</td><td class="value">{{ $invoice->billing_period }}</td></tr>
                                <tr><td class="label">Jatuh Tempo</td><td class="value">{{ $invoice->due_date?->format('d M Y') }}</td></tr>
                                <tr><td class="label">Status</td><td class="value"><strong>{{ $invoice->status_label }}</strong></td></tr>
                                <tr><td class="label">Total Tagihan</td><td class="value"><strong>@currency($invoice->amount)</strong></td></tr>
                            </table>
                        </div>
                    </td>
                    <td style="width: 50%; padding-left: 6px;">
                        <div class="panel">
                            <div class="panel-title">DITAGIHKAN KEPADA (BILL TO)</div>
                            <div class="panel-body">
                                <div class="name">{{ $invoice->customer?->name ?? '-' }}</div>
                                @if($invoice->customer?->customer_code) <div class="muted">Kode: {{ $invoice->customer->customer_code }}</div> @endif
                                @if($invoice->customer?->address) <div class="muted">{{ $invoice->customer->address }}</div> @endif
                                @if($invoice->customer?->phone) <div class="muted">Telp: {{ $invoice->customer->phone }}</div> @endif
                                @if($invoice->customer?->ppp_username) <div class="muted">Username PPPoE: {{ $invoice->customer->ppp_username }}</div> @endif
                                @if($invoice->package?->name) <div class="muted">Paket: {{ $invoice->package->name }}</div> @endif
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        <th>Deskripsi</th>
                        <th class="center" style="width: 52px;">Qty</th>
                        <th class="num" style="width: 110px;">Harga</th>
                        <th class="num" style="width: 130px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoice->items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="center">{{ $item->qty }}</td>
                            <td class="num">@currency($item->price)</td>
                            <td class="num">@currency($item->subtotal)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="center" style="color:#9ca3af">Tidak ada item.</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
                        <td colspan="4" class="num">TOTAL</td>
                        <td class="num total-amount">@currency($invoice->amount)</td>
                    </tr>
                </tbody>
            </table>

            <div class="terbilang">
                Terbilang: <strong>@terbilang($invoice->amount)</strong>
            </div>

            <div class="notes">
                @if($invoice->payment_method)
                    <p><strong>Metode Pembayaran:</strong> {{ $invoice->payment_method->label() }}</p>
                @endif
                @if($invoice->paid_at)
                    <p><strong>Dibayar pada:</strong> {{ $invoice->paid_at->format('d M Y H:i') }}</p>
                @endif
                @if($invoice->notes)
                    <p><strong>Catatan:</strong> {{ $invoice->notes }}</p>
                @endif
                @if(in_array($invoice->status->value, ['unpaid', 'overdue']))
                    <p>Mohon melakukan pembayaran sebelum tanggal jatuh tempo. Apabila tidak dibayar hingga tanggal {{ $invoice->isolation_day ? 'isolir pada tanggal '.$invoice->isolation_day : 'batas isolir' }}, layanan akan diisolir (diputus) sesuai ketentuan yang berlaku.</p>
                @endif
            </div>

            <table class="signatures">
                <tr>
                    <td>
                        <div class="label">Hormat Kami,<br>{{ $companyName }}</div>
                        <div class="line">{{ $companyName }}</div>
                    </td>
                    <td>
                        <div class="label">Penerima,<br>{{ $invoice->customer?->name ?? '' }}</div>
                        <div class="line">( {{ $invoice->customer?->name ?? '..........................' }} )</div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                Invoice ini dibuat otomatis oleh sistem. Nomor invoice: {{ $invoice->invoice_number }} &middot; Periode: {{ $invoice->billing_period }}
            </div>
        </div>
    @endforeach
</body>
</html>
