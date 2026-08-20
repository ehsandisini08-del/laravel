<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Invoice</title>
    <script>
        function printInvoices() {
            window.print();
        }
    </script>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1f2937;
            background: #f3f4f6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 24px;
            background: #111827;
            color: #fff;
        }
        .toolbar__title { font-size: 14px; font-weight: 600; }
        .toolbar__actions { display: flex; align-items: center; gap: 8px; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print { background: #10b981; color: #fff; }
        .btn-print:hover { background: #059669; }
        .btn-ghost { background: transparent; color: #d1d5db; border-color: #374151; }
        .btn-ghost:hover { color: #fff; }

        .sheets { padding: 24px; }
        .invoice-sheet {
            position: relative;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 24px;
            padding: 14mm 16mm;
            background: #fff;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            page-break-after: always;
        }
        .invoice-sheet:last-child { page-break-after: auto; }

        .invoice-header { display: flex; justify-content: space-between; gap: 20px; border-bottom: 3px solid #1d4ed8; padding-bottom: 10px; }
        .company-logo {
            width: 56px; height: 56px;
            display: flex; align-items: center; justify-content: center;
            background: #1d4ed8; color: #fff;
            font-size: 24px; font-weight: 800;
            border-radius: 10px;
            flex-shrink: 0;
        }
        .company-name { font-size: 20px; font-weight: 800; color: #111827; }
        .company-meta { margin-top: 4px; font-size: 11px; line-height: 1.5; color: #6b7280; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { margin: 0; font-size: 32px; letter-spacing: 2px; color: #1d4ed8; font-weight: 800; }
        .invoice-title p { margin: 4px 0 0; font-size: 13px; color: #4b5563; }

        .invoice-body { margin-top: 12px; display: flex; gap: 24px; }
        .invoice-meta { flex: 1; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .meta-row { display: flex; border-bottom: 1px solid #f3f4f6; }
        .meta-row:last-child { border-bottom: none; }
        .meta-label { width: 38%; padding: 7px 10px; font-size: 11px; font-weight: 600; color: #6b7280; background: #f9fafb; }
        .meta-value { flex: 1; padding: 7px 10px; font-size: 11px; color: #111827; }
        .meta-value.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .meta-value strong { color: #1d4ed8; }

        .bill-to { flex: 1; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .bill-to-head { padding: 6px 10px; font-size: 11px; font-weight: 700; color: #fff; background: #1d4ed8; }
        .bill-to-body { padding: 10px; font-size: 12px; line-height: 1.6; }
        .bill-to-body .name { font-size: 14px; font-weight: 700; color: #111827; }
        .bill-to-body .muted { color: #6b7280; }

        .stamp {
            position: absolute;
            top: 26mm;
            right: 16mm;
            transform: rotate(-12deg);
            border: 3px solid;
            border-radius: 8px;
            padding: 6px 18px;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 3px;
            opacity: 0.75;
            pointer-events: none;
        }
        .stamp-success { border-color: #059669; color: #059669; }
        .stamp-danger { border-color: #dc2626; color: #dc2626; }
        .stamp-default { border-color: #6b7280; color: #6b7280; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 12px; }
        .items-table thead th {
            text-align: left;
            padding: 8px 10px;
            background: #1d4ed8;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table th.num, .items-table td.num { text-align: right; }
        .items-table th.center, .items-table td.center { text-align: center; }
        .items-table tbody td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        .items-table tbody tr:nth-child(even) { background: #f9fafb; }
        .items-table .total-row td { padding: 10px; font-weight: 800; background: #eff6ff; border-bottom: none; }
        .total-amount { font-size: 16px; color: #1d4ed8; }

        .terbilang {
            margin-top: 12px;
            padding: 8px 10px;
            border: 1px dashed #9ca3af;
            border-radius: 6px;
            font-size: 11px;
            font-style: italic;
            color: #374151;
            background: #f9fafb;
        }
        .terbilang strong { font-style: normal; color: #111827; }

        .invoice-notes { margin-top: 14px; font-size: 11px; color: #6b7280; line-height: 1.6; }
        .invoice-notes strong { color: #111827; }

        .signatures { margin-top: 26px; display: flex; justify-content: space-between; gap: 40px; }
        .signature { flex: 1; text-align: center; }
        .signature .label { font-size: 11px; font-weight: 600; color: #6b7280; margin-bottom: 60px; }
        .signature .line { border-top: 1px solid #111827; padding-top: 4px; font-size: 12px; font-weight: 600; color: #111827; }

        .invoice-footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }

        @page { size: A4; margin: 0; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheets { padding: 0; }
            .invoice-sheet { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="toolbar__title">Pratinjau Invoice</div>
        <div class="toolbar__actions">
            <a href="javascript:history.back()" class="btn btn-ghost">&larr; Kembali</a>
            <button type="button" class="btn btn-print" onclick="printInvoices()">Cetak / Simpan PDF</button>
        </div>
    </div>

    <div class="sheets">
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
            <div class="invoice-sheet">
                @if(! in_array($invoice->status->value, ['draft', 'cancelled']))
                    <div class="stamp {{ $stampClass }}">{{ $stampText }}</div>
                @endif

                <div class="invoice-header">
                    <div class="invoice-header-left">
                        <div class="company-logo">{{ mb_strtoupper(mb_substr($companyName, 0, 1)) }}</div>
                        <div class="company-name" style="margin-top:8px">{{ $companyName }}</div>
                        <div class="company-meta">
                            @if(! empty($company['company_address'])) <div>{{ $company['company_address'] }}</div> @endif
                            <div>
                                @if(! empty($company['company_phone'])) <span>Telp: {{ $company['company_phone'] }}</span> @endif
                                @if(! empty($company['company_email'])) <span>@if(! empty($company['company_phone'])) &nbsp;|&nbsp; @endif Email: {{ $company['company_email'] }}</span> @endif
                            </div>
                            <div>
                                @if(! empty($company['company_tax_number'])) <span>NPWP: {{ $company['company_tax_number'] }}</span> @endif
                                @if(! empty($company['company_website'])) <span>@if(! empty($company['company_tax_number'])) &nbsp;|&nbsp; @endif {{ $company['company_website'] }}</span> @endif
                            </div>
                        </div>
                    </div>
                    <div class="invoice-title">
                        <h1>INVOICE</h1>
                        <p class="mono">{{ $invoice->invoice_number }}</p>
                    </div>
                </div>

                <div class="invoice-body">
                    <div class="invoice-meta">
                        <div class="meta-row">
                            <div class="meta-label">Tanggal Terbit</div>
                            <div class="meta-value">{{ $invoice->created_at?->format('d M Y') }}</div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-label">Periode Tagihan</div>
                            <div class="meta-value">{{ $invoice->billing_period }}</div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-label">Jatuh Tempo</div>
                            <div class="meta-value">{{ $invoice->due_date?->format('d M Y') }}</div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-label">Status</div>
                            <div class="meta-value"><strong>{{ $invoice->status_label }}</strong></div>
                        </div>
                        <div class="meta-row">
                            <div class="meta-label">Total Tagihan</div>
                            <div class="meta-value"><strong>@currency($invoice->amount)</strong></div>
                        </div>
                    </div>

                    <div class="bill-to">
                        <div class="bill-to-head">DITAGIHKAN KEPADA (BILL TO)</div>
                        <div class="bill-to-body">
                            <div class="name">{{ $invoice->customer?->name ?? '-' }}</div>
                            @if($invoice->customer?->customer_code)
                                <div class="muted">Kode: {{ $invoice->customer->customer_code }}</div>
                            @endif
                            @if($invoice->customer?->address)
                                <div class="muted">{{ $invoice->customer->address }}</div>
                            @endif
                            @if($invoice->customer?->phone)
                                <div class="muted">Telp: {{ $invoice->customer->phone }}</div>
                            @endif
                            @if($invoice->customer?->ppp_username)
                                <div class="muted">Username PPPoE: {{ $invoice->customer->ppp_username }}</div>
                            @endif
                            @if($invoice->package?->name)
                                <div class="muted">Paket: {{ $invoice->package->name }}</div>
                            @endif
                        </div>
                    </div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width:34px">No</th>
                            <th>Deskripsi</th>
                            <th class="center" style="width:52px">Qty</th>
                            <th class="num" style="width:110px">Harga</th>
                            <th class="num" style="width:130px">Subtotal</th>
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

                <div class="invoice-notes">
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

                <div class="signatures">
                    <div class="signature">
                        <div class="label">Hormat Kami,<br>{{ $companyName }}</div>
                        <div class="line">{{ $companyName }}</div>
                    </div>
                    <div class="signature">
                        <div class="label">Penerima,<br>{{ $invoice->customer?->name ?? '' }}</div>
                        <div class="line">( {{ $invoice->customer?->name ?? '..........................' }} )</div>
                    </div>
                </div>

                <div class="invoice-footer">
                    Invoice ini dibuat otomatis oleh sistem. Nomor invoice: {{ $invoice->invoice_number }} · Periode: {{ $invoice->billing_period }}
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>