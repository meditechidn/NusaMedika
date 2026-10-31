@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-[22px] font-bold text-slate-900 tracking-tight">Penerimaan Barang</h1>
        <p class="text-sm text-slate-500 mt-1">Catat penerimaan untuk pemesanan {{ $pemesanan->no_pemesanan }}. Jumlah terima dibatasi sisa pesanan; penerimaan boleh dilakukan bertahap.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('penerimaan_barang.create') }}" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <!-- Form Header -->
    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center gap-3">
        <div class="p-2 bg-emerald-100 text-emerald-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
        </div>
        <div>
            <h2 class="text-base font-semibold text-slate-800">Informasi Penerimaan</h2>
            <p class="text-xs text-slate-500 mt-0.5">Input faktur, tanggal terima, no. batch, dan jumlah barang yang diterima.</p>
        </div>
    </div>

    <!-- Form Body -->
    <div class="p-6">
        <form action="{{ route('penerimaan_barang.store') }}" method="POST" id="formTerimaBarang">
            @csrf
            <input type="hidden" name="pemesanan_id" value="{{ $pemesanan->pemesanan_id }}">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5">

                <!-- No Faktur -->
                <div>
                    <label for="no_faktur" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">No. Faktur</label>
                    <input type="text" id="no_faktur" name="no_faktur" value="{{ old('no_faktur') }}" placeholder="Contoh: INV-2026-0001"
                           class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 placeholder-slate-400">
                    @error('no_faktur')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tanggal Terima -->
                <div>
                    <label for="tanggal_terima" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Tanggal Terima</label>
                    <input type="date" id="tanggal_terima" name="tanggal_terima" value="{{ old('tanggal_terima', now()->format('Y-m-d')) }}"
                           class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                    @error('tanggal_terima')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- PO Info -->
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3.5 text-xs">
                    <p class="text-slate-500"><span class="font-semibold text-slate-600">No. Pemesanan:</span> {{ $pemesanan->no_pemesanan }}</p>
                    <p class="text-slate-500 mt-1"><span class="font-semibold text-slate-600">Bagian:</span> {{ $pemesanan->bagian->nama_bagian ?? '-' }}</p>
                </div>
            </div>

            <!-- Keterangan -->
            <div class="mt-5">
                <label for="keterangan" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Keterangan</label>
                <textarea id="keterangan" name="keterangan" rows="2" placeholder="Catatan tambahan penerimaan..."
                          class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 placeholder-slate-400 resize-none">{{ old('keterangan') }}</textarea>
                @error('keterangan')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Items -->
            <div class="mt-6">
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Item Penerimaan</h3>
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full text-left" style="min-width: 1250px;">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Barang</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Distributor</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Satuan</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Jumlah Pesan</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Sudah Diterima</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Sisa</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Jumlah Terima</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-left">No. Batch <span class="text-red-500" title="Wajib diisi">*</span></th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-left">Tgl Expired <span class="text-red-500" title="Wajib diisi">*</span></th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Harga Beli</th>
                                <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="text-[12px] divide-y divide-slate-100">
                            @foreach ($pemesanan->details as $d)
                                @php
                                    $info = $sisa[$d->barang_id] ?? [
                                        'pesan' => (float) $d->jumlah_pesan,
                                        'diterima' => 0,
                                        'sisa' => (float) $d->jumlah_pesan,
                                    ];
                                    $pr = (float) $info['sisa'] * (float) $d->harga_beli;
                                @endphp
                                <tr data-barang="{{ $d->barang_id }}" data-harga="{{ $d->harga_beli }}" data-subtotal="{{ $pr }}" class="{{ $info['sisa'] <= 0 ? 'opacity-60' : '' }}">
                                    <td class="px-3 py-3 text-slate-700">
                                        <input type="hidden" name="barang_id[]" value="{{ $d->barang_id }}">
                                        <span class="font-semibold text-slate-800">{{ $d->barang->nama_barang ?? '-' }}</span>
                                        @if ($d->barang && $d->barang->kode_barang)
                                            <span class="block text-[10px] text-slate-400">{{ $d->barang->kode_barang }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-slate-700">{{ $d->supplier->nama_supplier ?? '-' }}</td>
                                    <td class="px-3 py-3 text-slate-700">{{ $d->distributor->nama_supplier ?? '-' }}</td>
                                    <td class="px-3 py-3 text-center text-slate-600">{{ $d->barang->textSatuan() ?? '-' }}</td>
                                    <td class="px-3 py-3 text-right text-slate-700 tabular-nums">{{ rtrim(rtrim(number_format($info['pesan'], 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="px-3 py-3 text-right text-slate-700 tabular-nums">{{ rtrim(rtrim(number_format($info['diterima'], 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="px-3 py-3 text-right font-semibold tabular-nums {{ $info['sisa'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ rtrim(rtrim(number_format($info['sisa'], 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="px-3 py-3 text-right">
                                        @if ($info['sisa'] > 0)
                                            <input type="number" name="jumlah_terima[]" min="0" step="any" max="{{ $info['sisa'] }}" value="{{ old('jumlah_terima.'.$loop->index, $info['sisa']) }}" class="inp-terima text-sm w-full text-right border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none" style="min-width:90px;">
                                        @else
                                            <input type="number" name="jumlah_terima[]" min="0" step="any" value="0" readonly class="text-sm w-full text-right border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-100 text-slate-400 outline-none" style="min-width:90px;">
                                            <span class="block text-[10px] text-emerald-600 mt-0.5">Sudah lengkap</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-left">
                                        <input type="text" name="no_batch[]" value="" placeholder="mis. B001" maxlength="50" {{ $info['sisa'] <= 0 ? 'readonly' : '' }}
                                               class="inp-batch text-sm w-full border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none placeholder-slate-400 {{ $info['sisa'] <= 0 ? 'bg-slate-100' : '' }}" style="min-width:120px;">
                                    </td>
                                    <td class="px-3 py-3 text-left">
                                        <input type="date" name="tgl_expired[]" value="" {{ $info['sisa'] <= 0 ? 'readonly' : '' }}
                                               class="inp-expired text-sm w-full border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none placeholder-slate-400 {{ $info['sisa'] <= 0 ? 'bg-slate-100' : '' }}" style="min-width:130px;">
                                    </td>
                                    <td class="px-3 py-3 text-right text-slate-700 tabular-nums">{{ number_format((float) $d->harga_beli, 0, ',', '.') }}</td>
                                    <td class="px-3 py-3 text-right font-semibold text-slate-800 tabular-nums"><span class="txt-subtotal">Rp {{ number_format($pr, 0, ',', '.') }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <hr class="my-6 border-slate-200">

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('penerimaan_barang.create') }}" class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-slate-800 transition-colors shadow-sm text-center">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Simpan Penerimaan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('formTerimaBarang');

        form.querySelectorAll('tr[data-barang]').forEach(function (tr) {
            var harga = parseFloat(tr.dataset.harga || 0);
            var $inp = $(tr).find('.inp-terima');

            function hitung() {
                var jumlah = parseFloat($inp.val() || 0);
                var sub = jumlah * harga;
                tr.querySelector('.txt-subtotal').textContent = 'Rp ' + Number(sub).toLocaleString('id-ID');
                tr.dataset.subtotal = sub;
            }

            $inp.on('input', hitung);
            hitung();
        });
    });
</script>
@endpush