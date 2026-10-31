@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-6 flex justify-between items-end">
    <div>
        <h1 class="text-[22px] font-bold text-slate-900 tracking-tight">Buat Pesanan</h1>
        <p class="text-sm text-slate-500 mt-1">Buat dan kelola pemesanan barang (Purchase Order) ke supplier.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('buat_pesanan.create') }}" class="px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-0.5 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Buat Pesanan
        </a>
    </div>
</div>

<!-- Filter Card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
        <h2 class="text-sm font-semibold text-slate-700">Filter Pencarian</h2>
    </div>
    <form action="{{ route('buat_pesanan.index') }}" method="GET" id="filterForm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="col-span-1 md:col-span-2">
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Pencarian</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" id="search" name="search" value="{{ old('search', $search) }}" placeholder="Cari No. Pemesanan / Supplier..."
                           class="w-full text-sm border border-slate-200 rounded-lg pl-9 pr-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 placeholder-slate-400">
                </div>
            </div>
            <div>
                <label for="status" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Status</label>
                <select id="status" name="status" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                    <option value="">-- Semua Status --</option>
                    @foreach (\App\Models\Pemesanan::STATUS_LABEL as $val => $label)
                        <option value="{{ $val }}" @selected((string) old('status', $status) === (string) $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-2 mt-2 md:mt-0">
                <a href="{{ route('buat_pesanan.index') }}" class="inline-flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 text-sm font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-colors">Reset</a>
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
        <table class="w-full text-left" style="min-width: 1000px;">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">No.</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">No. Pemesanan</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Bagian Tujuan</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Jumlah Item</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Status</th>
                    <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-[12px] divide-y divide-slate-100">
                @forelse ($pemesananList as $i => $p)
@php
        $noUrut = ($pemesananList->firstItem() ?? 0) + $i;
        $badge = match ((int) $p->status_pemesanan) {
            1 => 'bg-blue-100 text-blue-700',
            2 => 'bg-emerald-100 text-emerald-700',
            3 => 'bg-red-100 text-red-600',
            4 => 'bg-indigo-100 text-indigo-700',
            default => 'bg-amber-100 text-amber-700',
        };
    @endphp
    <tr class="hover:bg-blue-50/40 transition-colors">
        <td class="px-3 py-3 text-center text-slate-500">{{ $noUrut }}</td>
        <td class="px-3 py-3 font-semibold text-slate-800">{{ $p->no_pemesanan }}</td>
        <td class="px-3 py-3 text-slate-600">{{ \Carbon\Carbon::parse($p->tanggal_pemesanan)->format('d-m-Y') }}</td>
        <td class="px-3 py-3 text-slate-600">{{ optional($p->bagian)->nama_bagian }}</td>
                        <td class="px-3 py-3 text-center text-slate-600">{{ $p->details->count() }}</td>
                        <td class="px-3 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold {{ $badge }}">
                                {{ $p->status_label }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                @if (in_array((int) $p->status_pemesanan, [2, 4], true))
                                    <a href="{{ route('pemesanan.cetak', $p->pemesanan_id) }}" target="_blank" class="cursor-pointer p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-md transition-colors" title="Cetak Surat Pesanan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    </a>
                                @endif
                                @if ((int) $p->status_pemesanan === 0)
                                    <a href="{{ route('buat_pesanan.edit', $p->pemesanan_id) }}" class="cursor-pointer p-1.5 text-blue-500 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors" title="Edit Data">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    <form action="{{ route('pemesanan.batal', $p->pemesanan_id) }}" method="POST" data-confirm-message="Yakin ingin membatalkan pemesanan ini?" data-confirm-danger="true" data-confirm-title="Batalkan" class="inline">
                                        @csrf
                                        <button type="submit" class="cursor-pointer p-1.5 text-amber-500 hover:bg-amber-50 hover:text-amber-600 rounded-md transition-colors" title="Batal Pemesanan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('buat_pesanan.destroy', $p->pemesanan_id) }}" method="POST" data-confirm-message="Yakin ingin menghapus pemesanan ini?" data-confirm-danger="true" data-confirm-title="Hapus Data" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cursor-pointer p-1.5 text-red-500 hover:bg-red-50 hover:text-red-600 rounded-md transition-colors" title="Hapus Data">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-12 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                <p class="text-sm font-medium">Belum ada data pemesanan.</p>
                                <p class="text-xs">Klik tombol "Buat Pesanan" untuk membuat pemesanan pertama.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/50">
        {{ $pemesananList->withQueryString()->links('components.pagination') }}
    </div>
</div>
@endsection