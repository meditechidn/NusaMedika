@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-6 flex justify-between items-end">
    <div>
        <h1 class="text-[22px] font-bold text-slate-900 tracking-tight">Stock Barang</h1>
        <p class="text-sm text-slate-500 mt-1">Posisi stock barang per bagian / gudang & no. batch.</p>
    </div>
</div>

<!-- Filter Card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
        <h2 class="text-sm font-semibold text-slate-700">Filter Pencarian</h2>
    </div>
    <form action="{{ route('stock_barang.index') }}" method="GET" id="filterForm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="bagian_id" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Bagian / Gudang</label>
                <select id="bagian_id" name="bagian_id" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                    <option value="">-- Semua Bagian --</option>
                    @foreach ($bagianList as $b)
                        <option value="{{ $b->bagian_id }}" @selected((string) old('bagian_id', $bagianId) === (string) $b->bagian_id)>{{ $b->nama_bagian }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-1 md:col-span-2">
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Pencarian</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="search" name="search" value="{{ old('search', $search) }}" placeholder="Cari Kode / Nama Barang..."
                           class="w-full text-sm border border-slate-200 rounded-lg pl-9 pr-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 placeholder-slate-400">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-2 md:mt-0">
                <a href="{{ route('stock_barang.index') }}" class="inline-flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 text-sm font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-colors">Reset</a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 px-6 rounded-lg shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Cari Data
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto drag-scroll">
        <table class="w-full text-left" style="min-width: 1100px;">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">No.</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kode</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Barang</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Jenis</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Satuan</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Bagian</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">No. Batch</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tgl Expired</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Harga Beli</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Harga Jual</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Stock</th>
                </tr>
            </thead>
            <tbody class="text-[12px] divide-y divide-slate-100">
                @forelse ($stockList as $i => $st)
                    @php
                        $noUrut = ($stockList->firstItem() ?? 0) + $i;
                        $jumlah = (float) $st->jumlah;
                    @endphp
                    <tr class="hover:bg-blue-50/40 transition-colors">
                        <td class="px-3 py-3 text-center text-slate-500">{{ $noUrut }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $st->barang->kode_barang ?? '-' }}</td>
                        <td class="px-3 py-3 font-semibold text-slate-800">{{ $st->barang->nama_barang ?? '-' }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $st->barang->jenis->nama_jenis_barang ?? '-' }}</td>
                        <td class="px-3 py-3 text-center text-slate-600">{{ $st->barang->textSatuan() }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $st->bagian->nama_bagian ?? '-' }}</td>
                        <td class="px-3 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold">{{ $st->no_batch ?: '-' }}</span>
                        </td>
                        <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $st->batchBarang && $st->batchBarang->tgl_expired ? \Carbon\Carbon::parse($st->batchBarang->tgl_expired)->format('d-m-Y') : '-' }}</td>
                        <td class="px-3 py-3 text-right text-slate-700 tabular-nums">{{ $st->batchBarang && $st->batchBarang->harga_beli !== null ? 'Rp '.number_format((float) $st->batchBarang->harga_beli, 0, ',', '.') : '-' }}</td>
                        <td class="px-3 py-3 text-right text-slate-700 tabular-nums">{{ $st->batchBarang && $st->batchBarang->harga_jual !== null ? 'Rp '.number_format((float) $st->batchBarang->harga_jual, 0, ',', '.') : '-' }}</td>
                        <td class="px-3 py-3 text-right font-bold text-slate-800 tabular-nums">{{ rtrim(rtrim(number_format($jumlah, 2, ',', '.'), '0'), ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-3 py-12 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                <p class="text-sm font-medium">Belum ada data stock.</p>
                                <p class="text-xs">Lakukan penerimaan barang untuk mengisi stock per bagian.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/50">
        {{ $stockList->withQueryString()->links('components.pagination') }}
    </div>
</div>
@endsection