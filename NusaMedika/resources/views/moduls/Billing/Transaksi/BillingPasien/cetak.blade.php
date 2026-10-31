<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Billing {{ $billing->no_billing ?: 'Draft' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        .head { display: flex; justify-content: space-between; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 14px; }
        .head h1 { font-size: 18px; margin: 0; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 4px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
        .right { text-align: right; }
        .total td { font-weight: bold; border-top: 2px solid #111; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
        .box { border: 1px solid #ddd; padding: 10px; }
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:12px"><button onclick="window.print()">Cetak</button></div>

    <div class="head">
        <div>
            <h1>RUMAH SAKIT NUSAMEDIKA</h1>
            <div class="muted">Tagihan Pasien</div>
        </div>
        <div style="text-align:right">
            <div><strong>{{ $billing->no_billing ?: 'Draft' }}</strong></div>
            <div class="muted">{{ now()->format('d/m/Y H:i') }}</div>
            <div class="muted">Status: {{ $billing->status_label }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="box">
            <div><strong>Pasien:</strong> {{ $billing->pasien->nama_pasien ?? '-' }}</div>
            <div><strong>No. RM:</strong> {{ $billing->pasien->no_mr ?? '-' }}</div>
            <div><strong>Jenis Rawat:</strong> {{ $billing->registrasi?->jenis_rawat ?: '-' }}</div>
            <div><strong>Kelas:</strong> {{ $billing->kelas->nama_kelas_ruang ?? '-' }}</div>
        </div>
        <div class="box">
            <div><strong>Registrasi:</strong> REG-{{ $billing->registrasi_id }}</div>
            <div><strong>Bagian:</strong> {{ $billing->registrasiDetail?->bagian?->nama_bagian ?? 'Layanan #'.$billing->registrasi_detail_id }}</div>
            <div><strong>Penjamin:</strong> {{ $billing->nasabah->nama_nasabah ?? 'Umum / Mandiri' }}</div>
            <div><strong>Tanggal Masuk:</strong> {{ $billing->tgl_masuk ? \Illuminate\Support\Carbon::parse($billing->tgl_masuk)->format('d/m/Y H:i') : '-' }}</div>
            <div><strong>Tanggal Keluar:</strong> {{ $billing->tgl_keluar ? \Illuminate\Support\Carbon::parse($billing->tgl_keluar)->format('d/m/Y H:i') : '-' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr><th>No</th><th>Layanan</th><th>Nama</th><th class="right">Qty</th><th class="right">Harga</th><th class="right">Subtotal</th></tr>
        </thead>
        <tbody>
            @forelse ($billing->details->sortBy('billing_detail_id') as $i => $item)
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
            <tr><td colspan="5" class="right">Subtotal</td><td class="right">{{ number_format((float) $billing->subtotal, 0, ',', '.') }}</td></tr>
            <tr><td colspan="5" class="right">Diskon</td><td class="right">{{ number_format((float) $billing->diskon, 0, ',', '.') }}</td></tr>
            <tr class="total"><td colspan="5" class="right">TOTAL</td><td class="right">{{ number_format((float) $billing->total, 0, ',', '.') }}</td></tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top:16px">Dicetak {{ now()->format('d/m/Y H:i') }} oleh {{ auth()->user()->nama_pegawai ?? auth()->user()->user_name ?? '-' }}</p>
</body>
</html>
