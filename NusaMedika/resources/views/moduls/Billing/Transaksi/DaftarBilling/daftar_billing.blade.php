@extends('layouts.app')

@section('content')
<x-page-header title="Daftar Billing" subtitle="Daftar tagihan pasien per episode kunjungan (RJ, IGD, rawat inap)." />

<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 mb-5">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <div class="lg:col-span-2">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cari</label>
            <input type="text" name="q" value="{{ $cari }}" placeholder="No. billing / closing / pasien / No. RM"
                   class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Jenis Rawat</label>
            <select name="jenis_rawat" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none">
                <option value="">Semua</option>
                @foreach (['RJ' => 'Rawat Jalan', 'RI' => 'Rawat Inap', 'IGD' => 'IGD', 'MCU' => 'MCU'] as $value => $label)
                    <option value="{{ $value }}" @selected((string) $jenisRawat === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status Billing</label>
            <select name="status" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none">
                <option value="">Semua Status</option>
                @foreach (\App\Models\Billing::STATUS_LABEL as $value => $label)
                    <option value="{{ $value }}" @selected((string) $status === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Dari Tanggal</label>
            <input type="date" name="dari" value="{{ $dari }}" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Sampai Tanggal</label>
            <input type="date" name="sampai" value="{{ $sampai }}" class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:border-blue-500 outline-none">
        </div>
        <div class="sm:col-span-2 lg:col-span-6 flex items-end gap-2">
            <button class="px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Cari</button>
            <a href="{{ route('daftar_billing.index') }}" class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Reset</a>
        </div>
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="text-left px-4 py-3">Episode</th>
                    <th class="text-left px-4 py-3">Pasien</th>
                    <th class="text-left px-4 py-3">Layanan</th>
                    <th class="text-left px-4 py-3">Bagian</th>
                    <th class="text-left px-4 py-3">Tanggal</th>
                    <th class="text-right px-4 py-3">Total</th>
                    <th class="text-center px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($billing as $episode)
                    @php
                        $adaTerbuka = $episode->status_ids->contains(\App\Models\Billing::OPEN);
                        $adaDitutup = $episode->status_ids->contains(\App\Models\Billing::CLOSED);
                        $adaBatal = $episode->status_ids->contains(\App\Models\Billing::VOID);
                        [$statusLabel, $badge] = match (true) {
                            $adaTerbuka => ['Terbuka', 'bg-amber-100 text-amber-700'],
                            $adaDitutup && $adaBatal => ['Sebagian', 'bg-slate-200 text-slate-700'],
                            $adaDitutup => ['Ditutup', 'bg-slate-200 text-slate-700'],
                            $adaBatal => ['Dibatalkan', 'bg-red-100 text-red-700'],
                            default => ['—', 'bg-slate-100 text-slate-500'],
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs font-semibold text-slate-700">EP-{{ $episode->root_id }}</div>
                            <div class="text-xs text-slate-400">{{ $episode->billings->count() }} billing</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-800">{{ $episode->pasien->nama_pasien ?? '—' }}</div>
                            <div class="text-xs text-slate-400">{{ $episode->pasien->no_mr ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($episode->jenis_rawat as $jenis)
                                    <span class="inline-flex px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[11px] font-semibold">{{ $jenis }}</span>
                                @empty
                                    <span class="text-slate-400">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600 max-w-64">
                            @if ($episode->bagian->isEmpty())
                                —
                            @else
                                {{ $episode->bagian->take(4)->implode(', ') }}
                                @if ($episode->bagian->count() > 4)
                                    <span class="text-slate-400">+{{ $episode->bagian->count() - 4 }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500">
                            @if ($episode->tgl_masuk)
                                <div>{{ \Illuminate\Support\Carbon::parse($episode->tgl_masuk)->format('d/m/Y') }}</div>
                            @endif
                            @if ($episode->tgl_terakhir)
                                <div class="text-xs text-slate-400">Tagihan {{ \Illuminate\Support\Carbon::parse($episode->tgl_terakhir)->format('d/m/Y H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($episode->total, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex px-2 py-1 rounded-full text-[11px] font-semibold {{ $badge }}">{{ $statusLabel }}</span>
                            @if ($episode->status_ids->count() > 1)
                                <div class="text-[11px] text-slate-400 mt-1">
                                    {{ $episode->billings->countBy(fn ($row) => $row->status_label)->map(fn ($jumlah, $label) => $label.' '.$jumlah)->implode(' · ') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('billing_pasien.index', ['registrasi_id' => $episode->root_id, 'kembali' => request()->fullUrl()]) }}"
                               class="px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg">Buka</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">Tidak ada billing pada filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($billing->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $billing->links() }}</div>
    @endif
</div>
@endsection
