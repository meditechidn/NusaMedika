@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-[22px] font-bold text-slate-900 tracking-tight">Kartu Stock</h1>
    <p class="text-sm text-slate-500 mt-1">Riwayat mutasi stock barang per bagian & no. batch (detail / summary / rekapitulasi).</p>
</div>

<!-- Filter Card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
        <h2 class="text-sm font-semibold text-slate-700">Filter Pencarian</h2>
    </div>
    <form action="{{ route('kartu_stock.index') }}" method="GET" id="filterForm">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label for="jenis" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Jenis</label>
                <select id="jenis" name="jenis" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                    @foreach ($jenisOpts as $val => $label)
                        <option value="{{ $val }}" @selected($jenis === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="barang_id" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Barang</label>
                <select id="barang_id" name="barang_id" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                    <option value="">-- Semua Barang --</option>
                    @foreach ($barangList as $b)
                        <option value="{{ $b->barang_id }}" @selected((string) old('barang_id', $barangId) === (string) $b->barang_id)>{{ $b->nama_barang }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="bagian_id" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Bagian</label>
                <select id="bagian_id" name="bagian_id" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                    <option value="">-- Semua Bagian --</option>
                    @foreach ($bagianList as $b)
                        <option value="{{ $b->bagian_id }}" @selected((string) old('bagian_id', $bagianId) === (string) $b->bagian_id)>{{ $b->nama_bagian }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="tanggal_awal" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Dari Tanggal</label>
                <input type="date" id="tanggal_awal" name="tanggal_awal" value="{{ old('tanggal_awal', $tanggalAwal) }}"
                       class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>
            <div>
                <label for="tanggal_akhir" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Sampai Tanggal</label>
                <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="{{ old('tanggal_akhir', $tanggalAkhir) }}"
                       class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-4">
            <a href="{{ route('kartu_stock.index') }}" class="inline-flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 text-sm font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-colors">Reset</a>
            <button type="submit" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 px-6 rounded-lg shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                Cari Data
            </button>
        </div>
    </form>
</div>

<!-- Summary -->
@if ($hasFilter)
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
        <div class="p-2.5 bg-emerald-100 text-emerald-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13a2 2 0 012 2v4a2 2 0 01-2 2H3m0-8v8m0 0l4-4m-4 4l4 4m7-1h6a2 2 0 002-2v-4"></path></svg>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Total Masuk</p>
            <p class="text-lg font-bold text-slate-800 tabular-nums">{{ number_format((float) $totMasuk, 0, ',', '.') }}</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
        <div class="p-2.5 bg-red-100 text-red-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13a2 2 0 012 2v4a2 2 0 01-2 2H3m0-8v8m0 0l4-4m-4 4l4 4"></path></svg>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">Total Keluar</p>
            <p class="text-lg font-bold text-slate-800 tabular-nums">{{ number_format((float) $totKeluar, 0, ',', '.') }}</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
        <div class="p-2.5 bg-blue-100 text-blue-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
        </div>
        <div>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wider">{{ $jenis === \App\Http\Controllers\Inventory\Stock\KartuStock\KartuStockController::JENIS_DETAIL ? 'Total Kartu' : 'Total Data' }}</p>
            <p class="text-lg font-bold text-slate-800 tabular-nums">{{ number_format($kartuList->total(), 0, ',', '.') }}</p>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto drag-scroll">
        @if ($jenis === \App\Http\Controllers\Inventory\Stock\KartuStock\KartuStockController::JENIS_DETAIL)
            <table class="w-full text-left" style="min-width: 1400px;">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">No.</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Bagian</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">No. Batch</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tgl Expired</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Jenis Mutasi</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Stock Awal</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Masuk</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Keluar</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Stock Akhir</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="text-[12px] divide-y divide-slate-100">
                    @forelse ($kartuList as $i => $k)
                        @php
                            $noUrut = ($kartuList->firstItem() ?? 0) + $i;
                            $run = $runningMap[$k->kartu_stock_id] ?? null;
                            $stockAwal = $run ? $run[0] : (float) $k->saldo_sebelum;
                            $stockAkhir = $run ? $run[1] : (float) $k->saldo_sesudah;
                            $badge = match ((int) $k->jenis_mutasi) {
                                0 => 'bg-slate-100 text-slate-600',
                                1 => 'bg-emerald-100 text-emerald-700',
                                2 => 'bg-blue-100 text-blue-700',
                                3 => 'bg-amber-100 text-amber-700',
                                4 => 'bg-violet-100 text-violet-700',
                                6 => 'bg-orange-100 text-orange-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-3 py-3 text-center text-slate-500">{{ $noUrut }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($k->tanggal)->format('d-m-Y') }}</td>
                            <td class="px-3 py-3 font-semibold text-slate-800">{{ $k->barang->nama_barang ?? '-' }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $k->bagian->nama_bagian ?? '-' }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold">{{ $k->no_batch ?: '-' }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $k->tgl_expired ? \Carbon\Carbon::parse($k->tgl_expired)->format('d-m-Y') : '-' }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badge }}">{{ $k->jenis_label }}</span>
                            </td>
                            <td class="px-3 py-3 text-right text-slate-600 tabular-nums">{{ rtrim(rtrim(number_format($stockAwal, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-3 py-3 text-right text-emerald-600 font-semibold tabular-nums">{{ $k->qty_masuk ? rtrim(rtrim(number_format((float) $k->qty_masuk, 2, ',', '.'), '0'), ',') : '-' }}</td>
                            <td class="px-3 py-3 text-right text-red-600 font-semibold tabular-nums">{{ $k->qty_keluar ? rtrim(rtrim(number_format((float) $k->qty_keluar, 2, ',', '.'), '0'), ',') : '-' }}</td>
                            <td class="px-3 py-3 text-right font-bold text-slate-800 tabular-nums">{{ rtrim(rtrim(number_format($stockAkhir, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-3 py-3 text-slate-500 max-w-[260px] truncate" title="{{ $k->keterangan }}">{{ $k->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-3 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-slate-400">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 9h2m-2 4h2m-6-4h.01M6 13h.01"></path></svg>
                                    <p class="text-sm font-medium">Belum ada riwayat kartu stock.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @elseif ($jenis === \App\Http\Controllers\Inventory\Stock\KartuStock\KartuStockController::JENIS_SUMMARY)
            <table class="w-full text-left" style="min-width: 1100px;">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">No.</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kode</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Satuan</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">No. Batch</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Total Masuk</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Total Keluar</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Stock Akhir</th>
                    </tr>
                </thead>
                <tbody class="text-[12px] divide-y divide-slate-100">
                    @forelse ($kartuList as $i => $k)
                        @php
                            $noUrut = ($kartuList->firstItem() ?? 0) + $i;
                            $saldo = (float) $k->tot_masuk - (float) $k->tot_keluar;
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-3 py-3 text-center text-slate-500">{{ $noUrut }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $k->kode_barang }}</td>
                            <td class="px-3 py-3 font-semibold text-slate-800">{{ $k->nama_barang }}</td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ $k->satuan ?? '-' }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold">{{ $k->no_batch ?: '-' }}</span>
                            </td>
                            <td class="px-3 py-3 text-right text-emerald-600 font-semibold tabular-nums">{{ rtrim(rtrim(number_format((float) $k->tot_masuk, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-3 py-3 text-right text-red-600 font-semibold tabular-nums">{{ rtrim(rtrim(number_format((float) $k->tot_keluar, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-3 py-3 text-right font-bold text-slate-800 tabular-nums">{{ rtrim(rtrim(number_format($saldo, 2, ',', '.'), '0'), ',') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-slate-400">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 9h2m-2 4h2m-6-4h.01M6 13h.01"></path></svg>
                                    <p class="text-sm font-medium">Belum ada ringkasan kartu stock.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @else
            <table class="w-full text-left" style="min-width: 900px;">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">No.</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kode</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Satuan</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Jumlah Batch</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Total Masuk</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Total Keluar</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Stock Akhir</th>
                    </tr>
                </thead>
                <tbody class="text-[12px] divide-y divide-slate-100">
                    @forelse ($kartuList as $i => $k)
                        @php
                            $noUrut = ($kartuList->firstItem() ?? 0) + $i;
                            $saldo = (float) $k->tot_masuk - (float) $k->tot_keluar;
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-3 py-3 text-center text-slate-500">{{ $noUrut }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $k->kode_barang }}</td>
                            <td class="px-3 py-3 font-semibold text-slate-800">{{ $k->nama_barang }}</td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ $k->satuan ?? '-' }}</td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold">{{ $k->jumlah_batch }}</span>
                            </td>
                            <td class="px-3 py-3 text-right text-emerald-600 font-semibold tabular-nums">{{ rtrim(rtrim(number_format((float) $k->tot_masuk, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-3 py-3 text-right text-red-600 font-semibold tabular-nums">{{ rtrim(rtrim(number_format((float) $k->tot_keluar, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-3 py-3 text-right font-bold text-slate-800 tabular-nums">{{ rtrim(rtrim(number_format($saldo, 2, ',', '.'), '0'), ',') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-slate-400">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 9h2m-2 4h2m-6-4h.01M6 13h.01"></path></svg>
                                    <p class="text-sm font-medium">Belum ada data rekapitulasi kartu stock.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>

    <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/50">
        {{ $kartuList->withQueryString()->links('components.pagination') }}
    </div>
</div>
@else
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
    <div class="flex flex-col items-center gap-3 text-slate-400">
        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
        <p class="text-sm font-medium text-slate-500">Silakan lakukan filter terlebih dahulu (pilih Barang / Bagian / rentang tanggal), lalu klik "Cari Data" untuk menampilkan kartu stock.</p>
    </div>
</div>
@endif
@endsection