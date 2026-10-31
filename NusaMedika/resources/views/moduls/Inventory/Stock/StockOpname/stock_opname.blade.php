@extends('layouts.app')

@section('content')

<!-- Page Header -->
<div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-end gap-3">
    <div>
        <h1 class="text-[22px] font-bold text-slate-900 tracking-tight">Stock Opname</h1>
        <p class="text-sm text-slate-500 mt-1">Sesuaikan jumlah stok sistem dengan hasil hitung fisik, atau hapus stok yang tidak valid.</p>
    </div>
</div>

<!-- Filter Card -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
        <h2 class="text-sm font-semibold text-slate-700">Filter Pencarian</h2>
    </div>
    <form action="{{ route('stock_opname.index') }}" method="GET">
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
                <a href="{{ route('stock_opname.index') }}" class="inline-flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 text-sm font-semibold py-2.5 px-4 rounded-lg shadow-sm transition-colors">Reset</a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 px-6 rounded-lg shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Cari Data
                </button>
            </div>
        </div>
    </form>
</div>

@if ($bagianTerpilih && $totalBagian > 0)
    <!-- Aksi massal per bagian -->
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-red-800">Hapus seluruh stok pada {{ $bagianTerpilih->nama_bagian }}</p>
            <p class="text-xs text-red-600 mt-0.5">{{ $totalBagian }} baris stok aktif akan dihapus (tercatat di Kartu Stock). Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <form id="formHapusSemua" action="{{ route('stock_opname.hapus_semua') }}" method="POST" class="hidden">
            @csrf
            <input type="hidden" name="bagian_id" value="{{ $bagianTerpilih->bagian_id }}">
        </form>
        <button type="button" onclick="bukaModalHapusSemua()" class="inline-flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-2.5 px-5 rounded-lg shadow-sm shadow-red-600/20 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"></path></svg>
            Hapus Semua Stock Bagian Ini
        </button>
    </div>

@push('scripts')
<script>
    function bukaModalHapusSemua() {
        window.nusaConfirm({
            title: 'Hapus Semua Stock?',
            message: 'Seluruh {{ $totalBagian }} baris stok aktif pada {{ $bagianTerpilih->nama_bagian }} akan dihapus. Lanjutkan?',
            confirmText: 'Ya, Hapus Semua',
            danger: true,
            onConfirm: function () {
                document.getElementById('formHapusSemua').submit();
            }
        });
    }
    window.bukaModalHapusSemua = bukaModalHapusSemua;
</script>
@endpush
@endif

<!-- Data Table -->
<form action="{{ route('stock_opname.store') }}" method="POST" id="opnameForm" data-confirm="true">
    @csrf
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <p class="text-xs text-slate-500">
                Isi <span class="font-semibold text-slate-700">Stok Fisik</span> sesuai hasil hitung. Hanya baris yang berbeda dari stok sistem yang disimpan.
                <span class="inline-flex items-center gap-1 ml-1 text-slate-600 font-semibold"><span id="jumlahBerubah">0</span> baris berubah.</span>
            </p>
            <button type="submit" id="simpanOpname" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2.5 px-5 rounded-lg shadow-sm shadow-emerald-600/20 transition-colors" @disabled($stockList->isEmpty())>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                Simpan Penyesuaian
            </button>
        </div>

        <div class="px-4 py-2.5 hidden bg-amber-50 border-b border-amber-200 text-xs font-medium text-amber-700" id="opnamePeringatan">
            Tidak ada perubahan untuk disimpan.
        </div>

        <div class="overflow-x-auto drag-scroll">
            <table class="w-full text-left" style="min-width: 1200px;">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">No.</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kode</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Barang</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Satuan</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Bagian</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">No. Batch</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tgl Expired</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Stok Sistem</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Stok Fisik</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Selisih</th>
                        <th class="px-3 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-[12px] divide-y divide-slate-100">
                    @forelse ($stockList as $i => $st)
                        @php
                            $noUrut = ($stockList->firstItem() ?? 0) + $i;
                            $jumlah = (float) $st->jumlah;
                            $jumlahTampil = rtrim(rtrim(number_format($jumlah, 2, ',', '.'), '0'), ',');
                            $jumlahInput = number_format($jumlah, 2, '.', '');
                            $labelHapus = ($st->barang->nama_barang ?? 'Barang').' - batch '.($st->no_batch ?: '-');
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition-colors opname-row" data-row>
                            <td class="px-3 py-3 text-center text-slate-500">{{ $noUrut }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $st->barang->kode_barang ?? '-' }}</td>
                            <td class="px-3 py-3 font-semibold text-slate-800">{{ $st->barang->nama_barang ?? '-' }}</td>
                            <td class="px-3 py-3 text-center text-slate-600">{{ $st->barang?->textSatuan() ?? '-' }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $st->bagian->nama_bagian ?? '-' }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold">{{ $st->no_batch ?: '-' }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $st->tgl_expired ? \Carbon\Carbon::parse($st->tgl_expired)->format('d-m-Y') : '-' }}</td>
                            <td class="px-3 py-3 text-right font-bold text-slate-800 tabular-nums">{{ $jumlahTampil }}</td>
                            <td class="px-3 py-3 text-center">
                                <input type="hidden" name="stock_id[]" value="{{ $st->stock_id }}">
                                <input type="number" name="stock_fisik[]" value="{{ old('stock_fisik.'.$i, $jumlahInput) }}" min="0" step="0.01" inputmode="decimal"
                                       data-sistem="{{ $jumlahInput }}"
                                       class="opname-fisik w-28 text-right text-sm border border-slate-200 rounded-lg px-2.5 py-2 bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 tabular-nums">
                            </td>
                            <td class="px-3 py-3 text-right font-semibold tabular-nums opname-selisih text-slate-400">0</td>
                            <td class="px-3 py-3 text-center">
                                <button type="button" data-stock-id="{{ $st->stock_id }}" data-label="Hapus stok {{ $st->barang->nama_barang ?? 'Barang' }} - batch {{ $st->no_batch ?: '-' }} pada {{ $st->bagian->nama_bagian ?? '-' }}? Tindakan ini tercatat di Kartu Stock." onclick="bukaModalHapusStock(this)"
                                        class="inline-flex items-center gap-1 bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold py-1.5 px-3 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"></path></svg>
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-12 text-center">
                                <div class="flex flex-col items-center gap-2 text-slate-400">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    <p class="text-sm font-medium">Belum ada data stok.</p>
                                    <p class="text-xs">Pilih bagian lain atau lakukan penerimaan barang terlebih dahulu.</p>
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
</form>

{{-- Form hapus per baris: di luar form penyesuaian agar tidak bersarang. --}}
@foreach ($stockList as $st)
    <form id="hapus-stock-{{ $st->stock_id }}"
          action="{{ route('stock_opname.destroy', ['stock_opname' => $st->stock_id]) }}"
          method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endforeach
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('opnameForm');
    if (! form) { return; }

    var inputs = Array.prototype.slice.call(document.querySelectorAll('.opname-fisik'));
    var counter = document.getElementById('jumlahBerubah');
    var peringatan = document.getElementById('opnamePeringatan');

    function formatAngka(nilai) {
        var bulat = Math.round(nilai * 100) / 100;
        return bulat.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }

    function hitung() {
        var berubah = 0;

        inputs.forEach(function (input) {
            var row = input.closest('[data-row]');
            var selisihCell = row ? row.querySelector('.opname-selisih') : null;
            var sistem = parseFloat(input.dataset.sistem || '0');
            var fisik = parseFloat(input.value);

            if (isNaN(fisik)) {
                if (selisihCell) {
                    selisihCell.textContent = '-';
                    selisihCell.className = 'px-3 py-3 text-right font-semibold tabular-nums opname-selisih text-slate-400';
                }
                return;
            }

            var selisih = Math.round((fisik - sistem) * 100) / 100;

            if (selisihCell) {
                if (selisih === 0) {
                    selisihCell.textContent = '0';
                    selisihCell.className = 'px-3 py-3 text-right font-semibold tabular-nums opname-selisih text-slate-400';
                } else {
                    selisihCell.textContent = (selisih > 0 ? '+' : '-') + formatAngka(Math.abs(selisih));
                    selisihCell.className = 'px-3 py-3 text-right font-bold tabular-nums opname-selisih ' + (selisih > 0 ? 'text-emerald-600' : 'text-red-600');
                }
            }

            if (row) {
                row.classList.toggle('bg-amber-50', selisih !== 0);
            }

            if (selisih !== 0) {
                berubah++;
            }
        });

        if (counter) {
            counter.textContent = berubah;
        }

        if (peringatan && berubah > 0) {
            peringatan.classList.add('hidden');
        }

        return berubah;
    }

    inputs.forEach(function (input) {
        input.addEventListener('input', hitung);
        input.addEventListener('change', hitung);
    });

    form.addEventListener('submit', function (e) {
        var berubah = hitung();
        if (berubah === 0) {
            e.preventDefault();
            if (peringatan) { peringatan.classList.remove('hidden'); }
            return;
        }
        form.setAttribute('data-confirm-title', 'Simpan Penyesuaian?');
        form.setAttribute('data-confirm-message', berubah + ' baris stok akan disesuaikan dengan jumlah fisik. Perubahan tercatat di Kartu Stock.');
        form.setAttribute('data-confirm-text', 'Ya, Simpan');
        form.setAttribute('data-confirm-danger', 'false');
    });

    function bukaModalHapusStock(btn) {
        var stockId = btn.getAttribute('data-stock-id');
        var label = btn.getAttribute('data-label');
        window.nusaConfirm({
            title: 'Hapus Stok?',
            message: label,
            confirmText: 'Ya, Hapus',
            danger: true,
            onConfirm: function () {
                document.getElementById('hapus-stock-' + stockId).submit();
            }
        });
    }

    window.bukaModalHapusStock = bukaModalHapusStock;

    hitung();
})();
</script>
@endpush
