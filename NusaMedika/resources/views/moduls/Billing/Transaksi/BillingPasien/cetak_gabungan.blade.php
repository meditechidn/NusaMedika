<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $judul }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        .head { display: flex; justify-content: space-between; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 14px; }
        .head h1 { font-size: 18px; margin: 0; }
        .muted { color: #666; }
        .registrasi { margin-bottom: 18px; }
        .registrasi-head { background: #f3f4f6; border-left: 4px solid #111; padding: 7px 10px; margin-bottom: 8px; display: flex; flex-wrap: wrap; gap: 10px; align-items: baseline; }
        .registrasi-head strong { font-size: 13px; }
        .billing { border: 1px solid #ddd; margin-bottom: 10px; page-break-inside: avoid; }
        .billing-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; padding: 8px 10px; background: #fafafa; border-bottom: 1px solid #ddd; }
        .billing-head strong { font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 5px 7px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        th { background: #f9fafb; font-size: 10px; text-transform: uppercase; color: #555; }
        .right { text-align: right; }
        tfoot td { font-weight: bold; }
        .grand { margin-top: 16px; margin-left: auto; width: 300px; }
        .grand table td { border: 0; padding: 4px 0; }
        .grand .total td { border-top: 2px solid #111; font-size: 14px; }
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:12px"><button onclick="window.print()">Cetak</button></div>

    <div class="head">
        <div>
            <h1>RUMAH SAKIT NUSAMEDIKA</h1>
            <div class="muted">{{ $judul }}</div>
        </div>
        <div style="text-align:right">
            <div><strong>{{ $pasien->no_mr ?? '-' }}</strong> · {{ $pasien->nama_pasien ?? '-' }}</div>
            <div class="muted">{{ now()->format('d/m/Y H:i') }}</div>
            <div class="muted">{{ $billings->count() }} billing</div>
        </div>
    </div>

    @forelse ($billings->groupBy('registrasi_id') as $registrasiId => $rows)
        @php
            $registrasi = $rows->first()->registrasi;
        @endphp
        <div class="registrasi">
            <div class="registrasi-head">
                <strong>REG-{{ $registrasiId }}</strong>
                <span>{{ $registrasi?->jenis_rawat ?: '-' }}</span>
                <span class="muted">Masuk {{ $registrasi?->tgl_masuk ? \Illuminate\Support\Carbon::parse($registrasi->tgl_masuk)->format('d/m/Y H:i') : '-' }}</span>
            </div>

            @foreach ($rows as $billing)
                @php
                    $items = $billing->details
                        ->filter(fn ($item) => (int) $item->status === \App\Models\BillingDetail::POSTED)
                        ->sortBy('billing_detail_id');
                @endphp
                <div class="billing">
                    <div class="billing-head">
                        <div>
                            <strong>{{ $billing->no_billing ?: 'Draft' }}</strong>
                            <span class="muted">· {{ $billing->registrasiDetail?->bagian?->nama_bagian ?? 'Layanan #'.$billing->registrasi_detail_id }}</span>
                        </div>
                        <div class="muted">
                            {{ $billing->kelas->nama_kelas_ruang ?? 'Tanpa kelas' }}
                            · {{ $billing->nasabah->nama_nasabah ?? 'Umum / Mandiri' }}
                            · {{ $billing->status_label }}
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Layanan</th>
                                <th>Nama</th>
                                <th class="right">Qty</th>
                                <th class="right">Harga</th>
                                <th class="right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $i => $item)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $item->jenis_label }}</td>
                                    <td>{{ $item->nama }}</td>
                                    <td class="right">{{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="right">{{ number_format((float) $item->harga_satuan, 0, ',', '.') }}</td>
                                    <td class="right">{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="muted">Tidak ada rincian.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr><td colspan="5" class="right">Subtotal</td><td class="right">{{ number_format((float) $billing->subtotal, 0, ',', '.') }}</td></tr>
                            <tr><td colspan="5" class="right">Diskon</td><td class="right">{{ number_format((float) $billing->diskon, 0, ',', '.') }}</td></tr>
                            <tr><td colspan="5" class="right">Total Billing</td><td class="right">{{ number_format((float) $billing->total, 0, ',', '.') }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            @endforeach
        </div>
    @empty
        <p class="muted">Belum ada billing untuk dicetak.</p>
    @endforelse

    <div class="grand">
        <table>
            <tr><td class="muted">Subtotal</td><td class="right">{{ number_format($totalSubtotal, 0, ',', '.') }}</td></tr>
            <tr><td class="muted">Diskon</td><td class="right">{{ number_format($totalDiskon, 0, ',', '.') }}</td></tr>
            <tr class="total"><td>TOTAL</td><td class="right">{{ number_format($totalTotal, 0, ',', '.') }}</td></tr>
        </table>
    </div>

    <p class="muted" style="margin-top:16px">Dicetak {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()->nama_pegawai ?? auth()->user()->user_name ?? '-' }}</p>
</body>
</html>
