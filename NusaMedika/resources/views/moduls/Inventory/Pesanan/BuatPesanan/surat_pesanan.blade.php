<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Pesanan - {{ $pemesanan->no_pemesanan }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #000; max-width: 780px; margin: 0 auto; padding: 24px; font-size: 12px; line-height: 1.45; }
        @media print {
            @page { size: A4; margin: 12mm; }
            body { padding: 0; max-width: none; }
            .no-print { display: none !important; }
        }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 16px; }
        .toolbar a, .toolbar button { font-family: Arial, sans-serif; font-size: 12px; padding: 8px 14px; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #334155; cursor: pointer; text-decoration: none; }
        .toolbar button { background: #2563eb; border-color: #2563eb; color: #fff; font-weight: bold; }
        .header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px; }
        .header h1 { font-size: 16px; letter-spacing: 1px; }
        .header .doc-no { font-size: 13px; font-weight: bold; }
        .title { font-size: 15px; font-weight: bold; text-align: center; letter-spacing: 1px; padding-bottom: 6px; margin-bottom: 12px; border-bottom: 1px solid #000; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.info td { padding: 3px 4px; vertical-align: top; font-size: 12px; }
        table.info td.label { font-weight: bold; width: 130px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th { border: 1px solid #000; padding: 5px 4px; text-align: left; font-size: 11px; background: #f1f1f1; }
        table.items td { border: 1px solid #000; padding: 5px 4px; vertical-align: top; font-size: 11px; }
        table.items .num { text-align: right; white-space: nowrap; }
        table.items .center { text-align: center; }
        tfoot td { font-weight: bold; }
        .footer { margin-top: 28px; display: flex; justify-content: space-between; align-items: flex-end; }
        .ttd { width: 180px; text-align: center; font-size: 11px; }
        .ttd .space { height: 56px; }
        .ttd .line { border-top: 1px solid #000; display: block; padding-top: 2px; }
        .note { margin-top: 10px; font-size: 11px; }
    </style>
</head>
<body onload="setTimeout(() => window.print(), 400);">
    <div class="toolbar no-print">
        <a href="{{ route('penerimaan_barang.index') }}">Kembali</a>
        <button type="button" onclick="window.print()">Cetak</button>
    </div>

    <div class="header">
        <div>
            <h1>NusaMedika</h1>
            <span>Instalasi Farmasi / Gudang</span>
        </div>
        <div class="doc-no">No. Pesanan: {{ $pemesanan->no_pemesanan }}</div>
    </div>

    <div class="title">SURAT PESANAN</div>

    <table class="info">
        <tr>
            <td class="label">No. Pemesanan</td>
            <td>{{ $pemesanan->no_pemesanan }}</td>
            <td class="label" style="width: 110px;">Tanggal</td>
            <td>{{ $pemesanan->tanggal_pemesanan ? \Carbon\Carbon::parse($pemesanan->tanggal_pemesanan)->format('d-m-Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="label">Bagian Tujuan</td>
            <td>{{ $pemesanan->bagian->nama_bagian ?? '-' }}</td>
            <td class="label">Status</td>
            <td>{{ $pemesanan->status_label }}</td>
        </tr>
    </table>

    @if ($pemesanan->keterangan)
        <div style="margin-bottom: 8px;"><strong>Keterangan:</strong> {{ $pemesanan->keterangan }}</div>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th class="center" style="width: 30px;">No</th>
                <th>Barang</th>
                <th>Supplier</th>
                <th>Distributor</th>
                <th class="center" style="width: 60px;">Satuan</th>
                <th class="num" style="width: 70px;">Jumlah</th>
                <th class="num" style="width: 85px;">Harga Beli</th>
                <th class="num" style="width: 95px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pemesanan->details as $i => $d)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>
                        {{ $d->barang->nama_barang ?? '-' }}
                        @if ($d->barang && $d->barang->kode_barang)
                            <span style="display: block; font-size: 10px; color: #555;">{{ $d->barang->kode_barang }}</span>
                        @endif
                    </td>
                    <td>{{ $d->supplier->nama_supplier ?? '-' }}</td>
                    <td>{{ $d->distributor->nama_supplier ?? '-' }}</td>
                    <td class="center">{{ $d->barang->textSatuan() ?? '-' }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format((float) $d->jumlah_pesan, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="num">{{ number_format((float) $d->harga_beli, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $d->subtotal, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">Tidak ada item.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7" class="num">Total Nilai</td>
                <td class="num">Rp {{ number_format($pemesanan->details->sum('subtotal'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="note">
        Penerimaan barang dilakukan bertahap sesuai sisa pesanan. Dokumen ini dicetak setelah seluruh
        jumlah pesanan diterima.
    </div>

    <div class="footer">
        <div class="ttd">
            Dibuat oleh,
            <div class="space"></div>
            <span class="line">{{ $pemesanan->bagian->nama_bagian ?? 'Petugas Gudang' }}</span>
        </div>
        <div class="ttd">
            Disetujui oleh,
            <div class="space"></div>
            <span class="line">Kepala Instalasi Farmasi</span>
        </div>
    </div>
</body>
</html>
