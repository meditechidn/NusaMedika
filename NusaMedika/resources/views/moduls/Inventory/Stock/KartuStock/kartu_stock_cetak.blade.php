<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Stock - {{ $jenisOpts[$jenis] ?? $jenis }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: #000; max-width: 1000px; margin: 0 auto; padding: 20px; font-size: 11px; }
        @media print { @page { size: A4 landscape; margin: 10mm; } body { padding: 0; max-width: none; } .no-print { display: none !important; } }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 12px; }
        .toolbar button { font-size: 11px; padding: 6px 12px; border: 1px solid #2563eb; background: #2563eb; color: #fff; border-radius: 4px; cursor: pointer; }
        h1 { font-size: 14px; text-align: center; margin-bottom: 4px; }
        .sub { text-align: center; font-size: 10px; color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #000; padding: 3px 4px; font-size: 10px; }
        th { background: #f1f1f1; font-weight: bold; }
        .num { text-align: right; }
        .center { text-align: center; }
        tfoot td { font-weight: bold; }
    </style>
</head>
<body onload="setTimeout(() => window.print(), 400);">
    <div class="toolbar no-print"><button onclick="window.print()">Cetak</button></div>
    <h1>KARTU STOCK - {{ strtoupper($jenisOpts[$jenis] ?? $jenis) }}</h1>
    <div class="sub">
        Jenis: {{ $jenisOpts[$jenis] ?? $jenis }}
        @if($barangId) | Barang ID: {{ $barangId }} @endif
        @if($bagianId) | Bagian ID: {{ $bagianId }} @endif
        @if($tanggalAwal) | Dari: {{ $tanggalAwal }} @endif
        @if($tanggalAkhir) | Sampai: {{ $tanggalAkhir }} @endif
    </div>

    @if ($jenis === \App\Http\Controllers\Inventory\Stock\KartuStock\KartuStockController::JENIS_DETAIL)
        <table>
            <thead>
                <tr>
                    <th class="center">No</th>
                    <th>Tanggal</th>
                    <th>Nama Barang</th>
                    <th>Bagian</th>
                    <th class="center">Jenis</th>
                    <th class="num">Stok Awal</th>
                    <th class="num">Masuk</th>
                    <th class="num">Keluar</th>
                    <th class="num">Stok Akhir</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 0; @endphp
                @foreach ($kartuList as $k)
                    @php
                        $no++;
                        $run = $runningMap[$k->kartu_stock_id] ?? null;
                        $sa = $run ? $run[0] : (float) $k->saldo_sebelum;
                        $sk = $run ? $run[1] : (float) $k->saldo_sesudah;
                    @endphp
                    <tr>
                        <td class="center">{{ $no }}</td>
                        <td>{{ \Carbon\Carbon::parse($k->tanggal)->format('d-m-Y') }}</td>
                        <td>{{ $k->barang->nama_barang ?? '-' }}</td>
                        <td>{{ $k->bagian->nama_bagian ?? '-' }}</td>
                        <td class="center">{{ $k->jenis_label }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($sa, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="num">{{ $k->qty_masuk ? rtrim(rtrim(number_format((float) $k->qty_masuk, 2, ',', '.'), '0'), ',') : '-' }}</td>
                        <td class="num">{{ $k->qty_keluar ? rtrim(rtrim(number_format((float) $k->qty_keluar, 2, ',', '.'), '0'), ',') : '-' }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($sk, 2, ',', '.'), '0'), ',') }}</td>
                        <td>{{ $k->keterangan ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif ($jenis === \App\Http\Controllers\Inventory\Stock\KartuStock\KartuStockController::JENIS_SUMMARY)
        <table>
            <thead>
                <tr>
                    <th class="center">No</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th class="center">Satuan</th>
                    <th class="center">No. Batch</th>
                    <th class="num">Stok Awal</th>
                    <th class="num">Masuk</th>
                    <th class="num">Keluar</th>
                    <th class="num">Stok Akhir</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 0; @endphp
                @foreach ($kartuList as $k)
                    @php
                        $no++;
                        $saldo = (float) ($k->stock_awal ?? 0) + (float) $k->tot_masuk - (float) $k->tot_keluar;
                    @endphp
                    <tr>
                        <td class="center">{{ $no }}</td>
                        <td>{{ $k->kode_barang }}</td>
                        <td>{{ $k->nama_barang }}</td>
                        <td class="center">{{ $k->satuan ?? '-' }}</td>
                        <td class="center">{{ $k->no_batch ?: '-' }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) ($k->stock_awal ?? 0), 2, ',', '.'), '0'), ',') }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $k->tot_masuk, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $k->tot_keluar, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($saldo, 2, ',', '.'), '0'), ',') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th class="center">No</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th class="center">Satuan</th>
                    <th class="center">Jml Batch</th>
                    <th class="num">Stok Awal</th>
                    <th class="num">Masuk</th>
                    <th class="num">Keluar</th>
                    <th class="num">Stok Akhir</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 0; @endphp
                @foreach ($kartuList as $k)
                    @php
                        $no++;
                        $saldo = (float) ($k->stock_awal ?? 0) + (float) $k->tot_masuk - (float) $k->tot_keluar;
                    @endphp
                    <tr>
                        <td class="center">{{ $no }}</td>
                        <td>{{ $k->kode_barang }}</td>
                        <td>{{ $k->nama_barang }}</td>
                        <td class="center">{{ $k->satuan ?? '-' }}</td>
                        <td class="center">{{ $k->jumlah_batch }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) ($k->stock_awal ?? 0), 2, ',', '.'), '0'), ',') }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $k->tot_masuk, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format((float) $k->tot_keluar, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($saldo, 2, ',', '.'), '0'), ',') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
