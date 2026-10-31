@extends('layouts.app')

@section('content')
@php
    $bolehBilling = \App\Helpers\BillingAkses::can(\App\Helpers\BillingAkses::BILLING_PASIEN);
    $bolehTutup = $bolehBilling;
    $bolehAturTarif = \App\Helpers\BillingAkses::can(\App\Helpers\BillingAkses::MASTER_TARIF);
    $opsiObat = $barangOptions->filter(fn ($b) => strtolower((string) $b->jenis?->nama_jenis_barang) === 'obat')->values();
    $opsiBmhp = $barangOptions->filter(fn ($b) => strtolower((string) $b->jenis?->nama_jenis_barang) === 'bmhp')->values();
    $billingMap = $billings->keyBy('billing_id');
    $adaTerbuka = $billings->contains(fn ($row) => $row->is_terbuka);
    $adaDitutup = $billings->contains(fn ($row) => (int) $row->status_billing === \App\Models\Billing::CLOSED);
    $adaBatal = $billings->contains(fn ($row) => (int) $row->status_billing === \App\Models\Billing::VOID);
    [$statusEpisode, $badgeEpisode] = match (true) {
        $adaTerbuka => ['Terbuka', 'bg-amber-100 text-amber-700'],
        $adaDitutup && $adaBatal => ['Sebagian', 'bg-slate-200 text-slate-700'],
        $adaDitutup => ['Ditutup', 'bg-slate-200 text-slate-700'],
        $adaBatal => ['Dibatalkan', 'bg-red-100 text-red-700'],
        default => ['—', 'bg-slate-100 text-slate-500'],
    };
    $jenisUnik = $registrasis->sortBy(fn ($reg) => sprintf('%s-%010d', $reg->tgl_masuk ?? '', $reg->registrasi_id))->pluck('jenis_rawat')->filter()->unique()->values();
    $kelasUnik = $billings->pluck('kelas.nama_kelas_ruang')->filter()->unique()->values();
    $penjaminUnik = $billings->pluck('nasabah.nama_nasabah')->filter()->unique()->values();
    $pasien = $rootRegistrasi->pasien;
@endphp

<x-page-header title="Billing Pasien" subtitle="Episode EP-{{ $rootRegistrasi->registrasi_id }} · {{ $pasien->nama_pasien ?? '—' }}">
    <a href="{{ $kembali }}" class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Kembali</a>
</x-page-header>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mb-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Pasien</p>
            <p class="text-lg font-bold text-slate-900">{{ $pasien->nama_pasien ?? '—' }}</p>
            <p class="text-sm text-slate-500">
                {{ $pasien->no_mr ?? '' }} · Episode EP-{{ $rootRegistrasi->registrasi_id }} ·
                {{ $billings->count() }} billing · {{ $sections->count() }} bagian layanan
            </p>
            <div class="flex flex-wrap gap-1 mt-2">
                @foreach ($jenisUnik as $jenis)
                    <span class="inline-flex px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-[11px] font-semibold">{{ $jenis }}</span>
                @endforeach
            </div>
        </div>
        <div class="flex flex-wrap gap-4 text-sm">
            <div><span class="text-slate-400 block text-xs">Episode</span><span class="font-mono">EP-{{ $rootRegistrasi->registrasi_id }}</span></div>
            <div><span class="text-slate-400 block text-xs">Kelas</span>{{ $kelasUnik->isEmpty() ? '—' : $kelasUnik->implode(', ') }}</div>
            <div><span class="text-slate-400 block text-xs">Penjamin</span>{{ $penjaminUnik->isEmpty() ? 'Umum / Mandiri' : $penjaminUnik->implode(', ') }}</div>
            <div><span class="text-slate-400 block text-xs">Status</span><span class="inline-flex px-2 py-1 rounded-full text-[11px] font-semibold {{ $badgeEpisode }}">{{ $statusEpisode }}</span></div>
        </div>
    </div>
</div>

@if ($adminTarifKosong)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-amber-800">
            Biaya administrasi masih <strong>Rp0</strong> karena tarif administrasi belum diatur pada master tarif.
        </p>
        @if ($bolehAturTarif)
            <a href="{{ route('admin.master_tarif.index', ['search' => 'ADMINISTRASI']) }}"
               class="px-3 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg">Atur Tarif Administrasi</a>
        @endif
    </div>
@endif

<div class="space-y-5">
    @forelse ($sections as $section)
        @php
            $sectionBillings = $section['billings']->sortBy('billing_id')->values();
            $sectionStatusIds = $sectionBillings->pluck('status_billing')->map(fn ($value) => (int) $value)->unique();
            $sectionTerbuka = $section['billing_terbuka'];
            [$statusSection, $badgeSection] = match (true) {
                $sectionStatusIds->contains(\App\Models\Billing::OPEN) => ['Terbuka', 'bg-amber-100 text-amber-700'],
                $sectionStatusIds->contains(\App\Models\Billing::CLOSED) => ['Ditutup', 'bg-slate-200 text-slate-700'],
                $sectionStatusIds->contains(\App\Models\Billing::VOID) => ['Dibatalkan', 'bg-red-100 text-red-700'],
                default => ['—', 'bg-slate-100 text-slate-500'],
            };
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-bold text-slate-800">{{ $section['nama_bagian'] }}</h2>
                        <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badgeSection }}">{{ $statusSection }}</span>
                        @if ($section['registrasi'])
                            <span class="text-[11px] font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ $section['registrasi']->jenis_rawat }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        Detail #{{ $section['detail_id'] }}
                        @if ($section['detail']?->tgl_daftar)
                            · Daftar {{ \Illuminate\Support\Carbon::parse($section['detail']->tgl_daftar)->format('d/m/Y H:i') }}
                        @endif
                        @if ($section['registrasi'])
                            · REG-{{ $section['registrasi']->registrasi_id }}
                        @endif
                        · {{ $section['items']->count() }} item · Rp {{ number_format($section['total'], 0, ',', '.') }}
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider">
                        <tr>
                            <th class="text-left px-4 py-3">Nama</th>
                            <th class="text-right px-4 py-3">Qty</th>
                            <th class="text-right px-4 py-3">Harga</th>
                            <th class="text-right px-4 py-3">Subtotal</th>
                            <th class="text-right px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($section['items']->sortBy('billing_detail_id') as $item)
                            @php
                                $itemBilling = $billingMap->get($item->billing_id);
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $item->nama }}</div>
                                    <div class="text-xs text-slate-400">
                                        {{ $itemBilling?->no_billing ?: 'Draft billing' }}
                                        @if ($item->tanggal)
                                            · {{ \Illuminate\Support\Carbon::parse($item->tanggal)->format('d/m/Y') }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">{{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format((float) $item->harga_satuan, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($bolehBilling && $itemBilling?->is_terbuka)
                                        <form method="POST" action="{{ route('billing_pasien.void_item', ['billing' => $itemBilling->billing_id, 'detail' => $item->billing_detail_id]) }}"
                                              data-confirm-message="Batalkan item ini?" data-confirm-danger="true" data-confirm-title="Void Item">
                                            @csrf
                                            <input type="hidden" name="alasan" value="Dibatalkan dari halaman billing">
                                            <button class="inline-flex items-center px-2.5 py-1 text-[11px] font-semibold text-white bg-red-600 hover:bg-red-700 rounded">Void Item</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada rincian billing pada bagian ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-slate-100 space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($sectionBillings as $billing)
                        @php
                            $badgeBilling = match ((int) $billing->status_billing) {
                                \App\Models\Billing::CLOSED => 'bg-slate-200 text-slate-700',
                                \App\Models\Billing::VOID => 'bg-red-100 text-red-700',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        @endphp
                        <div class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <span class="font-mono text-xs text-slate-700">{{ $billing->no_billing ?: 'Draft' }}</span>
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badgeBilling }}">{{ $billing->status_label }}</span>
                            <span class="text-xs text-slate-500">Rp {{ number_format((float) $billing->total, 0, ',', '.') }}</span>
                            @if (! $billing->is_terbuka && (int) $billing->status_billing === \App\Models\Billing::CLOSED && $bolehTutup)
                                <form method="POST" action="{{ route('billing_pasien.buka', ['billing' => $billing->billing_id]) }}"
                                      data-confirm-message="Buka kembali billing {{ $billing->no_billing }}?" data-confirm-title="Buka Billing">
                                    @csrf
                                    <button class="px-2.5 py-1 text-[11px] font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded">Buka Billing</button>
                                </form>
                            @endif
                            @if ($billing->is_terbuka && $bolehBilling)
                                <form method="POST" action="{{ route('billing_pasien.void', ['billing' => $billing->billing_id]) }}"
                                      class="flex items-center gap-1" data-confirm-message="Batalkan billing {{ $billing->no_billing }}?" data-confirm-danger="true" data-confirm-title="Batalkan Billing">
                                    @csrf
                                    <input type="text" name="alasan" required placeholder="Alasan void" class="w-32 text-[11px] border border-slate-200 rounded px-2 py-1 bg-white">
                                    <button class="px-2 py-1 text-[11px] font-semibold text-white bg-red-600 hover:bg-red-700 rounded">Void Billing</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($bolehBilling && $sectionTerbuka->isNotEmpty())
                    @foreach ($sectionTerbuka as $billing)
                        <form method="POST" action="{{ route('billing_pasien.item', ['billing' => $billing->billing_id]) }}"
                              class="grid grid-cols-1 sm:grid-cols-12 gap-2" data-item-form>
                            @csrf
                            <input type="hidden" name="billing_id" value="{{ $billing->billing_id }}">
                            <input type="hidden" name="registrasi_detail_id" value="{{ $billing->registrasi_detail_id }}">
                            <div class="sm:col-span-6">
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Tambah Tindakan / Obat / BMHP — {{ $billing->no_billing ?: 'Draft' }}</label>
                                <select name="item" required class="select2 w-full"
                                        data-harga-url="{{ route('billing_pasien.harga') }}"
                                        data-billing-id="{{ $billing->billing_id }}">
                                    <option value="">Pilih item...</option>
                                    <optgroup label="Tindakan">
                                        @foreach ($tindakanOptions as $tindakan)
                                            <option value="TINDAKAN:{{ $tindakan->tindakan_id }}" @selected(old('item') === 'TINDAKAN:'.$tindakan->tindakan_id)>
                                                {{ $tindakan->kode_tindakan }} · {{ $tindakan->nama_tindakan }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Obat">
                                        @foreach ($opsiObat as $obat)
                                            <option value="OBAT:{{ $obat->barang_id }}" @selected(old('item') === 'OBAT:'.$obat->barang_id)>
                                                {{ $obat->kode_barang }} · {{ $obat->nama_barang }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="BMHP">
                                        @foreach ($opsiBmhp as $bmhp)
                                            <option value="BMHP:{{ $bmhp->barang_id }}" @selected(old('item') === 'BMHP:'.$bmhp->barang_id)>
                                                {{ $bmhp->kode_barang }} · {{ $bmhp->nama_barang }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Qty</label>
                                <input type="number" name="qty" value="{{ old('billing_id') == $billing->billing_id ? old('qty', 1) : 1 }}" min="0.01" step="0.01" required class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-slate-50">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-500 mb-1">Harga</label>
                                <div data-harga-display class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-slate-100 text-slate-600">Rp 0</div>
                            </div>
                            <div class="sm:col-span-2 flex items-end">
                                <button class="w-full px-3 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Tambah</button>
                            </div>
                            @if (old('billing_id') == $billing->billing_id && $errors->any())
                                <div class="sm:col-span-12 text-xs text-red-600">{{ $errors->first() }}</div>
                            @endif
                        </form>
                    @endforeach
                @endif

                @if ($bolehBilling)
                    @foreach ($sectionTerbuka as $billing)
                        <form method="POST" action="{{ route('billing_pasien.diskon', ['billing' => $billing->billing_id]) }}" class="flex items-end gap-2 max-w-sm">
                            @csrf
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Diskon {{ $billing->no_billing ?: 'Draft' }}</label>
                                <input type="number" name="diskon" value="{{ (float) $billing->diskon }}" min="0" max="{{ (float) $billing->subtotal }}" step="0.01" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 bg-slate-50">
                            </div>
                            <button class="px-3 py-2 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Simpan</button>
                        </form>
                    @endforeach
                @endif
            </div>
        </div>
    @empty
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-12 text-center text-slate-500">
            Belum ada rincian billing pada episode ini.
        </div>
    @endforelse
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 mt-5">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 text-xs text-slate-500">
            Total episode dihitung dari seluruh billing pada rangkaian kunjungan pasien.
        </div>
        <div class="text-sm space-y-1">
            <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="font-semibold">{{ number_format($totalSubtotal, 0, ',', '.') }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Diskon</span><span>{{ number_format($totalDiskon, 0, ',', '.') }}</span></div>
            <div class="flex justify-between pt-2 border-t border-slate-200"><span class="font-bold text-slate-800">Total</span><span class="font-bold text-slate-900">{{ number_format($totalBilling, 0, ',', '.') }}</span></div>
        </div>
    </div>
</div>

<div class="mt-5 flex flex-wrap items-center justify-end gap-2">
    <a href="{{ route('billing_pasien.cetak_episode', ['registrasi_id' => $rootRegistrasi->registrasi_id]) }}" target="_blank"
       class="px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Cetak Episode</a>
    <a href="{{ route('billing_pasien.cetak_pasien', ['pasien_id' => $pasien->pasien_id ?? 0]) }}" target="_blank"
       class="px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Cetak Seluruh Billing</a>
    @if ($adaDitutup && $bolehTutup)
        <form method="POST" action="{{ route('billing_pasien.buka_semua') }}"
              data-confirm-message="Buka semua billing tertutup pada episode ini?"
              data-confirm-title="Buka Semua Billing">
            @csrf
            <input type="hidden" name="registrasi_id" value="{{ $rootRegistrasi->registrasi_id }}">
            <button class="px-4 py-2.5 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-lg">Buka Semua Billing</button>
        </form>
    @endif
    @if ($adaTerbuka && $bolehTutup)
        <form method="POST" action="{{ route('billing_pasien.tutup_semua') }}"
              data-confirm-message="Tutup semua billing terbuka pada episode ini?"
              data-confirm-title="Tutup Semua Billing">
            @csrf
            <input type="hidden" name="registrasi_id" value="{{ $rootRegistrasi->registrasi_id }}">
            <button class="px-4 py-2.5 text-sm font-semibold text-white bg-slate-800 hover:bg-slate-900 rounded-lg">Tutup Semua Billing</button>
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-item-form]').forEach(function (form) {
        var select = form.querySelector('select[name="item"]');
        var display = form.querySelector('[data-harga-display]');
        if (!select || !display) return;

        var permintaan = 0;

        function rupiah(nilai) {
            return 'Rp ' + Number(nilai || 0).toLocaleString('id-ID');
        }

        function muatHarga() {
            var nilai = select.value || '';
            var bagian = nilai.split(':');
            if (bagian.length !== 2) {
                display.textContent = 'Rp 0';
                return;
            }

            var urutan = ++permintaan;
            var url = new URL(select.dataset.hargaUrl, window.location.origin);
            url.searchParams.set('billing_id', select.dataset.billingId);
            url.searchParams.set('jenis', bagian[0]);
            url.searchParams.set('item_id', bagian[1]);

            fetch(url, {headers: {'Accept': 'application/json'}})
                .then(function (res) {
                    if (!res.ok) throw new Error('Gagal memuat harga');
                    return res.json();
                })
                .then(function (data) {
                    if (urutan !== permintaan) return;
                    display.textContent = Number(data.harga) > 0 ? rupiah(data.harga) : 'Rp 0 (belum diatur)';
                })
                .catch(function () {
                    if (urutan !== permintaan) return;
                    display.textContent = 'Gagal memuat';
                });
        }

        // Select2 memicu event lewat jQuery, bukan addEventListener biasa.
        if (window.jQuery) {
            window.jQuery(select).on('change', muatHarga);
        } else {
            select.addEventListener('change', muatHarga);
        }

        if (select.value) muatHarga();
    });
});
</script>
@endpush
