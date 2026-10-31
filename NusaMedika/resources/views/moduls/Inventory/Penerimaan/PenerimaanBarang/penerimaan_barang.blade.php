@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-6 flex justify-between items-end">
    <div>
        <h1 class="text-[22px] font-bold text-slate-900 tracking-tight">Penerimaan Barang</h1>
        <p class="text-sm text-slate-500 mt-1">Rekap penerimaan barang berbasis pesanan (PO) yang telah disetujui.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('penerimaan_barang.create') }}" class="px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-0.5 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Proses Penerimaan
        </a>
    </div>
</div>

<!-- Filter Card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
        <h2 class="text-sm font-semibold text-slate-700">Filter Pencarian</h2>
    </div>
    <form action="{{ route('penerimaan_barang.index') }}" method="GET" id="filterForm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="col-span-1 md:col-span-3">
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Pencarian</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="search" name="search" value="{{ old('search', $search) }}" placeholder="Cari No. Faktur / Supplier / No. Pemesanan..."
                           class="w-full text-sm border border-slate-200 rounded-lg pl-9 pr-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 placeholder-slate-400">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-2 md:mt-0">
                <a href="{{ route('penerimaan_barang.index') }}" class="inline-flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 text-sm font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-colors">Reset</a>
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
        <table class="w-full text-left" style="min-width: 900px;">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">No.</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">No. Faktur</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">No. Pemesanan</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Bagian</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Terima</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Jumlah Item</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Status Pesanan</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-[12px] divide-y divide-slate-100">
                @forelse ($penerimaanList as $i => $r)
                    @php
                        $noUrut = ($penerimaanList->firstItem() ?? 0) + $i;
                    @endphp
                    <tr class="hover:bg-blue-50/40 transition-colors">
                        <td class="px-3 py-3 text-center text-slate-500">{{ $noUrut }}</td>
                        <td class="px-3 py-3 font-semibold text-slate-800">{{ $r->no_faktur ?: '-' }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $r->pemesanan->no_pemesanan ?? '-' }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $r->bagian->nama_bagian ?? '-' }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ \Carbon\Carbon::parse($r->tanggal_terima)->format('d-m-Y') }}</td>
                        <td class="px-3 py-3 text-center text-slate-600">{{ $r->details->count() }}</td>
                        <td class="px-3 py-3 text-center">
                            @php
                                $statusPo = (int) ($r->pemesanan->status_pemesanan ?? 0);
                                $badgePo = match ($statusPo) {
                                    2 => 'bg-emerald-100 text-emerald-700',
                                    3 => 'bg-red-100 text-red-600',
                                    4 => 'bg-indigo-100 text-indigo-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold {{ $badgePo }}">
                                {{ $r->pemesanan->status_label ?? '-' }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <a href="{{ route('pemesanan.cetak', $r->pemesanan_id) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-semibold text-slate-600 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 hover:text-slate-800 transition-colors" title="Cetak Surat Pesanan">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                Surat Pesanan
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-12 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                <p class="text-sm font-medium">Belum ada data penerimaan barang.</p>
                                <p class="text-xs">Klik tombol "Proses Penerimaan" untuk mencatat penerimaan pertama.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/50">
        {{ $penerimaanList->withQueryString()->links('components.pagination') }}
    </div>
</div>
@endsection