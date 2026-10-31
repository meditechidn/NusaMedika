@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-[22px] font-bold text-slate-900 tracking-tight">Dispense Resep</h1>
        <p class="text-sm text-slate-500 mt-1">Siapkan obat dari stok {{ $depo->nama_bagian }} sesuai item resep.</p>
    </div>
    <a href="{{ route('list_pesanan_resep.index') }}" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-2 shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
    </a>
</div>

<!-- Info Resep -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-5">
    <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
        </div>
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <h2 class="text-base font-bold text-slate-800">{{ $resep->no_resep }}</h2>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">{{ $resep->status_label }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-1 mt-1.5 text-xs text-slate-600">
                <p><span class="text-slate-400">Pasien:</span> <span class="font-semibold text-slate-800">{{ $resep->pasien->nama_pasien ?? '-' }} ({{ $resep->pasien->no_mr ?? '-' }})</span></p>
                <p><span class="text-slate-400">Dokter:</span> <span class="font-semibold text-slate-800">{{ $resep->dokter->nama_pegawai ?? '-' }}</span></p>
                <p><span class="text-slate-400">Tanggal:</span> {{ \Carbon\Carbon::parse($resep->tanggal_resep)->format('d-m-Y H:i') }}</p>
                <p><span class="text-slate-400">Depo:</span> <span class="font-semibold text-emerald-700">{{ $depo->nama_bagian }}</span></p>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <form action="{{ route('list_pesanan_resep.dispense_store', $resep->peresepan_obat_id) }}" method="POST" id="formDispense">
        @csrf

        <div class="overflow-x-auto drag-scroll">
            <table class="w-full text-left" style="min-width: 760px;">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="px-5 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Obat</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Jumlah Resep</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center">Sudah Dispense</th>
                        <th class="px-3 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">No. Batch (Stok {{ $depo->nama_bagian }})</th>
                        <th class="px-5 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-right">Jumlah Dispense</th>
                    </tr>
                </thead>
                <tbody class="text-[12px] divide-y divide-slate-100">
                    @foreach ($resep->details as $d)
                        @php
                            $sisa = (float) $d->jumlah - (float) ($d->jumlah_dispense ?? 0);
                            $options = $stocks->get($d->barang_id, collect());
                            $available = $options->where('jumlah', '>', 0);
                        @endphp
                        <tr>
                            <td class="px-5 py-3 text-slate-700">
                                <span class="font-semibold text-slate-800">{{ $d->barang->nama_barang ?? '-' }}</span>
                                @if ($d->barang && $d->barang->kode_barang)
                                    <span class="block text-[10px] text-slate-400">{{ $d->barang->kode_barang }}</span>
                                @endif
                                @if ($d->s_1 || $d->s_2 || $d->aturan_pakai)
                                    <span class="block text-[10px] text-slate-400 mt-0.5">{{ $d->s_1 }} {{ $d->s_2 }} @if ($d->aturan_pakai) ({{ $d->aturan_pakai }}) @endif</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center text-slate-700 tabular-nums">{{ rtrim(rtrim(number_format((float) $d->jumlah, 2, ',', '.'), '0'), ',') }}</td>
                            <td class="px-3 py-3 text-center tabular-nums">
                                @if ((float) ($d->jumlah_dispense ?? 0) > 0)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">{{ rtrim(rtrim(number_format((float) $d->jumlah_dispense, 2, ',', '.'), '0'), ',') }}</span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <select name="no_batch[{{ $d->peresepan_obat_detail_id }}]" class="batch-select text-sm w-full border border-slate-200 rounded-lg px-2 py-2 bg-slate-50 outline-none text-slate-700" style="min-width: 200px;">
                                    <option value="">-- Pilih Batch / Lewati --</option>
                                    @forelse ($available as $s)
                                        <option value="{{ $s->no_batch }}||{{ $s->harga_jual ?? '' }}" @selected(old('no_batch.'.$d->peresepan_obat_detail_id) === $s->no_batch)>
                                            {{ $s->no_batch }} (stok {{ rtrim(rtrim(number_format((float) $s->jumlah, 2, ',', '.'), '0'), ',') }})
                                            @if ($s->harga_jual !== null) - Harga Jual Rp {{ number_format((float) $s->harga_jual, 0, ',', '.') }} @endif
                                            @if ($s->batchBarang && $s->batchBarang->tgl_expired) - Exp {{ $s->batchBarang->tgl_expired }} @endif
                                        </option>
                                    @empty
                                        <option value="" disabled>Stok tidak tersedia</option>
                                    @endforelse
                                </select>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <input type="number" name="jumlah_dispense[{{ $d->peresepan_obat_detail_id }}]" min="0" step="any"
                                       max="{{ $sisa }}" value="{{ old('jumlah_dispense.'.$d->peresepan_obat_detail_id, rtrim(rtrim(number_format($sisa, 2, ',', '.'), '0'), ',')) }}"
                                       class="text-sm w-28 text-right border border-slate-200 rounded-lg px-2.5 py-2 bg-slate-50 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none text-slate-700">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($resep->keterangan)
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/40 text-xs text-slate-500">
                <span class="font-semibold text-slate-600">Keterangan:</span> {{ $resep->keterangan }}
            </div>
        @endif

        <hr class="border-slate-200">

        <div class="px-5 py-4 flex flex-col gap-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none" title="Centang bila seluruh obat pada resep sudah selesai disiapkan">
                    <input type="checkbox" name="selesai" value="1" @checked(old('selesai'))
                           class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500/30">
                    <span class="text-sm font-semibold text-slate-700">Selesai</span>
                </label>
                <span class="text-[11px] text-slate-400">Jika tidak dicentang, status resep menjadi "Dispense Sebagian" dan tetap tampil di daftar.</span>
            </div>
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('list_pesanan_resep.index') }}" class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-sm text-center">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm shadow-emerald-600/20 transition-all hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Simpan Dispense
                </button>
            </div>
        </div>
    </form>
</div>

@if (session('success') || session('error'))
    <script>document.title = "Dispense Resep";</script>
@endif
@endsection