@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-[22px] font-bold text-slate-900 tracking-tight">Buat Mutasi Barang</h1>
        <p class="text-sm text-slate-500 mt-1">Mutasi barang antar bagian, atau catat pemakaian / pengeluaran stok. Kosongkan Bagian Tujuan untuk mencatat pemakaian.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('mutasi_barang.index') }}" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <!-- Form Header -->
    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center gap-3">
        <div class="p-2 bg-emerald-100 text-emerald-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
        </div>
        <div>
            <h2 class="text-base font-semibold text-slate-800">Informasi Mutasi</h2>
            <p class="text-xs text-slate-500 mt-0.5">Tentukan bagian asal / tujuan serta item barang yang dimutasikan.</p>
        </div>
    </div>

    <!-- Form Body -->
    <div class="p-6">
        <form action="{{ route('mutasi_barang.store') }}" method="POST" id="formMutasiBarang">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5">

                <!-- Bagian Asal -->
                <div>
                    <label for="bagian_asal_id" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Bagian Asal <span class="text-red-500">*</span></label>
                    <select id="bagian_asal_id" name="bagian_asal_id" required
                            class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                        <option value="">-- Pilih Bagian Asal --</option>
                        @foreach ($bagianList as $b)
                            <option value="{{ $b->bagian_id }}" @selected((string) old('bagian_asal_id') === (string) $b->bagian_id)>{{ $b->nama_bagian }}</option>
                        @endforeach
                    </select>
                    @error('bagian_asal_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bagian Tujuan -->
                <div>
                    <label for="bagian_tujuan_id" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Bagian Tujuan</label>
                    <select id="bagian_tujuan_id" name="bagian_tujuan_id"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                        <option value="">-- Pemakaian / Tidak Ada Tujuan --</option>
                        @foreach ($bagianList as $b)
                            <option value="{{ $b->bagian_id }}" @selected((string) old('bagian_tujuan_id') === (string) $b->bagian_id)>{{ $b->nama_bagian }}</option>
                        @endforeach
                    </select>
                    @error('bagian_tujuan_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal -->
                <div>
                    <label for="tanggal_mutasi" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Tanggal Mutasi</label>
                    <input type="date" id="tanggal_mutasi" name="tanggal_mutasi" value="{{ old('tanggal_mutasi', now()->format('Y-m-d')) }}"
                           class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                    @error('tanggal_mutasi')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Keterangan -->
            <div class="mt-5">
                <label for="keterangan" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Keterangan</label>
                <textarea id="keterangan" name="keterangan" rows="2" placeholder="Catatan tambahan mutasi..."
                          class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 placeholder-slate-400 resize-none">{{ old('keterangan') }}</textarea>
                @error('keterangan')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Items -->
            <div class="mt-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-800">Item Barang</h3>
                    <button type="button" id="btnTambahItem" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Tambah Item
                    </button>
                </div>
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full text-left" style="min-width: 700px;">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Barang</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Satuan</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-left">No. Batch <span class="text-red-500">*</span></th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Jumlah</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">#</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyItem" class="divide-y divide-slate-100">
                            <tr id="rowPlaceholder" class="text-center">
                                <td colspan="5" class="px-3 py-6 text-xs text-slate-400">Belum ada item. Klik "Tambah Item" untuk menambahkan barang.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <hr class="my-6 border-slate-200">

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('mutasi_barang.index') }}" class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-slate-800 transition-colors shadow-sm text-center">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Simpan Mutasi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var apiUrl = "{{ route('api.barang.search') }}";
        var tbody = document.getElementById('tbodyItem');
        var placeholder = document.getElementById('rowPlaceholder');
        var bagianAsal = document.getElementById('bagian_asal_id');
        var counter = 0;
        var BATCH_STOCK = @json($batchStock);

        function batchOptions(barangId) {
            var asal = bagianAsal ? bagianAsal.value : '';
            var opts = [];
            BATCH_STOCK.forEach(function (s) {
                if (String(s.barang_id) !== String(barangId)) return;
                if (asal && String(s.bagian_id) !== String(asal)) return;
                opts.push(s);
            });
            return opts;
        }

        function renderBatch(tr) {
            var sel = tr.querySelector('select.batch-select');
            if (!sel) return;
            var barangId = tr.querySelector('select.barang-select').value;
            var opts = barangId ? batchOptions(barangId) : [];
            var current = sel.value;
            sel.innerHTML = '';
            if (!barangId) {
                var ph0 = document.createElement('option');
                ph0.value = '';
                ph0.textContent = 'Pilih barang dulu';
                ph0.selected = true;
                sel.appendChild(ph0);
                return;
            }
            if (!opts.length) {
                var ph1 = document.createElement('option');
                ph1.value = '';
                ph1.textContent = 'Tidak ada batch pada bagian asal';
                ph1.selected = true;
                sel.appendChild(ph1);
                return;
            }
            var ph = document.createElement('option');
            ph.value = '';
            ph.textContent = '-- Pilih Batch --';
            ph.selected = true;
            sel.appendChild(ph);
            opts.forEach(function (o) {
                var opt = document.createElement('option');
                opt.value = o.id_batch;
                var label = o.no_batch + ' (stok ' + Number(o.jumlah).toLocaleString('id-ID') + ')';
                if (o.harga_jual !== null) label += ' - Harga Jual Rp ' + Number(o.harga_jual).toLocaleString('id-ID');
                if (o.tgl_expired) label += ' - Exp ' + o.tgl_expired;
                opt.textContent = label;
                if (String(current) === o.no_batch) opt.selected = true;
                sel.appendChild(opt);
            });
        }

        function addRow() {
            counter++;
            var tr = document.createElement('tr');
            tr.dataset.item = counter;
            tr.innerHTML = [
                '<td class="px-3 py-2 align-top">',
                '   <select name="barang_id[]" class="barang-select text-sm w-full" style="min-width:220px;"></select>',
                '</td>',
                '<td class="px-3 py-2 align-top text-center"><span class="text-satuan text-xs text-slate-600">-</span></td>',
                '<td class="px-3 py-2 align-top">',
                '   <select name="id_batch[]" class="batch-select text-sm w-full border border-slate-200 rounded-lg px-2 py-2 bg-slate-50 outline-none text-slate-700" style="min-width:150px;"></select>',
                '</td>',
                '<td class="px-3 py-2 align-top">',
                '   <input type="number" name="jumlah[]" min="1" step="any" value="1" class="inp-jumlah text-sm w-full text-right border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none" style="min-width:90px;">',
                '</td>',
                '<td class="px-3 py-2 align-top text-center">',
                '   <button type="button" class="btn-hapus-item p-1.5 text-red-500 hover:bg-red-50 rounded-md transition-colors" title="Hapus Item">',
                '       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>',
                '   </button>',
                '</td>'
            ].join('');

            placeholder.style.display = 'none';
            tbody.appendChild(tr);

            var $tr = $(tr);
            var $sel = $tr.find('select.barang-select');
            window.initBarangSelect($sel, apiUrl);
            renderBatch(tr);

            $sel.on('select2:select', function () {
                var opt = $(this).find('option:selected');
                tr.querySelector('.text-satuan').textContent = opt.attr('data-satuan') || '-';
                renderBatch(tr);
            });

            $tr.find('.btn-hapus-item').on('click', function () {
                $sel.select2('destroy');
                tr.remove();
                if (!tbody.querySelector('tr[data-item]')) {
                    placeholder.style.display = '';
                }
            });
        }

        if (bagianAsal) {
            bagianAsal.addEventListener('change', function () {
                tbody.querySelectorAll('tr[data-item]').forEach(function (tr) {
                    renderBatch(tr);
                });
            });
        }

        document.getElementById('btnTambahItem').addEventListener('click', function () { addRow(); });

        addRow();
    })();
</script>
@endpush