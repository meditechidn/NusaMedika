@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-6 flex justify-between items-end">
    <div>
        <h1 class="text-[22px] font-bold text-slate-900 tracking-tight">List Pelayanan Pasien</h1>
        <p class="text-sm text-slate-500 mt-1">Daftar riwayat kunjungan dan pelayanan medis pasien.</p>
    </div>
    <div class="text-right">
        <p class="text-sm font-semibold text-red-500">
            {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }} WIB
        </p>
    </div>
</div>

<!-- Filter Card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
        <h2 class="text-sm font-semibold text-slate-700">Filter Pencarian</h2>
    </div>
    <form action="{{ route('list_pelayanan_pasien.index') }}" method="GET" id="filterForm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label for="tanggal_awal" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Tanggal Awal</label>
                <input type="date" id="tanggal_awal" name="tanggal_awal" value="{{ $tanggalAwal }}" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>
            
            <div>
                <label for="tanggal_akhir" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Tanggal Akhir</label>
                <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="{{ $tanggalAkhir }}" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
            </div>

            <div>
                <x-select_pasien
                    label="No. MR / Nama Pasien"
                    :selected="isset($pasienId) && $pasienId != '' ? $pasienId : null"
                />
            </div>
            
            <div>
                <label for="jenis_layanan" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Jenis Layanan</label>
                <div class="relative">
                    <select id="jenis_layanan" name="jenis_layanan" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 appearance-none">
                        <option value="" {{ request('jenis_layanan') == '' ? 'selected' : '' }}>-- Pilih Layanan --</option>
                        <option value="{{ env('JENIS_RAWAT_IGD', 'IGD') }}" {{ request('jenis_layanan') == env('JENIS_RAWAT_IGD', 'IGD') ? 'selected' : '' }}>IGD</option>
                        <option value="{{ env('JENIS_RAWAT_RI', 'RI') }}" {{ request('jenis_layanan') == env('JENIS_RAWAT_RI', 'RI') ? 'selected' : '' }}>RAWAT INAP</option>
                        <option value="{{ env('JENIS_RAWAT_RJ', 'RJ') }}" {{ request('jenis_layanan') == env('JENIS_RAWAT_RJ', 'RJ') ? 'selected' : '' }}>RAWAT JALAN</option>
                        <option value="{{ env('JENIS_RAWAT_MCU', 'MCU') }}" {{ request('jenis_layanan') == env('JENIS_RAWAT_MCU', 'MCU') ? 'selected' : '' }}>MEDICAL CHECKUP</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </div>
            
            <div class="col-span-1 md:col-span-4 flex justify-end gap-2 mt-2">
                <a href="{{ route('list_pelayanan_pasien.index') }}" title="Reset Filter" class="inline-flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 text-sm font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-colors">
                    Reset
                </a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold py-2.5 px-6 rounded-lg shadow-sm transition-colors duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Cari Data
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    @if(isset($kunjungan) && count($kunjungan) > 0)
        <div class="overflow-x-auto drag-scroll">
            <table class="w-full text-left" style="min-width: 1400px;">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">No.</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Pasien</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">No. Regis</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tgl Kontrol</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nasabah</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Layanan</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Rujukan/SEP</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">DPJP</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Status</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-[12px] divide-y divide-slate-100">
                    @foreach($kunjungan as $idx => $row)
                        @php
                            $isClosed = $row->billing && (int) $row->billing->status_selesai === 1;
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors">
                            <td class="px-3 py-3 text-center text-slate-500">
                                {{ ($kunjungan->currentPage() - 1) * $kunjungan->perPage() + $idx + 1 }}
                            </td>
                            <td class="px-3 py-3 font-semibold text-slate-800">
                                {{ $row->pasien->nama_pasien . ' ('. $row->pasien->no_mr .')'?? '-' }}
                            </td>
                            <td class="px-3 py-3">
                                <span class="text-[10px] text-slate-400">{{ $row->registrasi_id }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-600">
                                {{ $row->tgl_masuk ? date('d-m-Y', strtotime($row->tgl_masuk)) : '-' }}
                            </td>
                            <td class="px-3 py-3 text-slate-600">
                                @if($row->pasienNasabah)
                                    {{ $row->pasienNasabah->nasabah->nama_nasabah ?? '-' }}
                                    @if($row->pasien_nasabah_id_2)
                                        <br><span class="text-[10px] font-semibold text-slate-500">1 Lainnya</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600">
                                @foreach($row->registrasiDetails as $detail)
                                    @if($detail->bagian)
                                        {{ $detail->bagian->nama_bagian }}
                                        <!-- Medis (Dashboard EMR) -->
                                        <a href="{{ route('dashboard_pasien.index', $detail->registrasi_detail_id) }}" class="cursor-pointer p-1.5 text-rose-500 hover:bg-rose-50 hover:text-rose-600 rounded-md transition-colors" title="Dashboard Medis">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                        </a>
                                    @endif
                                @endforeach
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($row->rujukanSep && $row->rujukanSep->sep)
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-slate-700 text-white text-[11px] font-bold shadow-sm">
                                        {{ $row->rujukanSep->sep }}
                                    </span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @foreach($row->penanggungRawat as $dpjp)
                                    @if($dpjp->user)
                                        <span class="inline-flex items-center px-2 py-1 rounded bg-blue-600 text-white text-[11px] font-bold shadow-sm mb-1">
                                            {{ $dpjp->user->nama_pegawai ?? $dpjp->user->user_name }}
                                        </span><br>
                                    @endif
                                @endforeach
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($isClosed)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-[11px] font-bold bg-amber-500 text-white shadow-sm">
                                        Tutup Bill
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-[11px] font-bold bg-red-500 text-white shadow-sm">
                                        Belum
                                    </span>
                                @endif
                                
                                @if($row->flag_online)
                                    <br>
                                    <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded bg-emerald-500 text-white text-[10px] font-bold shadow-sm">
                                        M-JKN
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <!-- Edit Nasabah -->
                                    <button type="button" class="cursor-pointer p-1.5 text-blue-500 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors" title="Edit Nasabah">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                                    </button>
                                    
                                    <!-- Create SEP -->
                                    <button type="button" class="cursor-pointer p-1.5 text-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 rounded-md transition-colors" title="Create SEP">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </button>

                                    <!-- Edit SEP -->
                                    <button type="button" class="cursor-pointer p-1.5 text-amber-500 hover:bg-amber-50 hover:text-amber-600 rounded-md transition-colors" title="Edit SEP">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <!-- Pisah Layanan -->
                                    <button type="button" class="cursor-pointer p-1.5 text-indigo-500 hover:bg-indigo-50 hover:text-indigo-600 rounded-md transition-colors" title="Pisah Layanan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    </button>

                                    <!-- Delete -->
                                    @if($isClosed)
                                        <button disabled class="p-1.5 text-slate-300 cursor-not-allowed rounded-md" title="Billing Sudah Ditutup">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    @else
                                        <form action="{{ route('list_pelayanan_pasien.destroy', $row->registrasi_id) }}" method="POST" class="inline-block" data-confirm-message="Anda Yakin? Jika Anda melanjutkan, maka akan menghapus semua layanan pasien tersebut." data-confirm-danger="true" data-confirm-title="Hapus Data">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cursor-pointer p-1.5 text-red-500 hover:bg-red-50 hover:text-red-600 rounded-md transition-colors" title="Delete Layanan">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/50">
            {{ $kunjungan->links('components.pagination') }}
        </div>
    @else
        <div class="p-12 text-center">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-slate-50 mb-3 border border-slate-100">
                <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <p class="text-sm text-slate-500 font-medium">Tidak ada data pelayanan yang sesuai dengan filter.</p>
            <p class="text-xs text-slate-400 mt-1">Coba ubah kriteria pencarian Anda.</p>
        </div>
    @endif
</div>
@endsection