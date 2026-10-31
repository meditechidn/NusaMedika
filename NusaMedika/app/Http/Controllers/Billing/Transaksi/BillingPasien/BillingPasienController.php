<?php

namespace App\Http\Controllers\Billing\Transaksi\BillingPasien;

use App\Helpers\BillingAkses;
use App\Helpers\BillingHelper;
use App\Helpers\RegistrasiEpisodeHelper;
use App\Helpers\TarifHelper;
use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Billing;
use App\Models\BillingDetail;
use App\Models\Pasien;
use App\Models\Registrasi;
use App\Models\RegistrasiDetail;
use App\Models\Tindakan;
use Illuminate\Http\Request;
use RuntimeException;

class BillingPasienController extends Controller
{
    public function index(Request $request)
    {
        BillingAkses::authorizeAny([BillingAkses::BILLING_PASIEN, BillingAkses::DAFTAR_BILLING]);

        $rootId = $this->resolveEpisodeRoot($request);
        if (! $rootId) {
            return redirect()->route('daftar_billing.index')->with('error', 'Pilih billing pasien yang akan dibuka.');
        }

        $rootRegistrasi = Registrasi::with('pasien')->find($rootId);
        if (! $rootRegistrasi) {
            return redirect()->route('daftar_billing.index')->with('error', 'Registrasi billing tidak ditemukan.');
        }

        $episode = $this->muatEpisode($rootId);

        $kembali = (string) $request->get('kembali');
        $basis = rtrim(url('/'), '/');
        $kembaliLokal = $kembali === $basis
            || str_starts_with($kembali, $basis.'/')
            || str_starts_with($kembali, $basis.'?');
        if (! $kembaliLokal || $kembali === $request->fullUrl()) {
            $kembali = route('daftar_billing.index');
        }

        $tindakanOptions = Tindakan::aktif()->orderBy('nama_tindakan')->get(['tindakan_id', 'kode_tindakan', 'nama_tindakan']);
        $barangOptions = Barang::with(['jenis', 'satuan'])
            ->aktif()
            ->orderBy('nama_barang')
            ->get()
            ->filter(fn (Barang $barang) => in_array(strtolower((string) $barang->jenis?->nama_jenis_barang), ['obat', 'bmhp'], true))
            ->values();

        $semuaItem = $episode['billings']->flatMap->details
            ->filter(fn (BillingDetail $item) => (int) $item->status === BillingDetail::POSTED);
        $adminTarifKosong = $semuaItem->contains(fn (BillingDetail $item) => $item->kode === 'ADMIN' && (float) $item->harga_satuan <= 0);

        return view('moduls.Billing.Transaksi.BillingPasien.billing_pasien', array_merge($episode, [
            'rootRegistrasi' => $rootRegistrasi,
            'kembali' => $kembali,
            'tindakanOptions' => $tindakanOptions,
            'barangOptions' => $barangOptions,
            'adminTarifKosong' => $adminTarifKosong,
        ]));
    }

    /**
     * Satu episode = seluruh registrasi yang terhubung lewat referensi_registrasi_id.
     * Halaman menerima billing/registrasi_detail/registrasi_id lalu selalu membuka root episode.
     */
    private function resolveEpisodeRoot(Request $request): ?int
    {
        $id = $request->route('billing_pasien')
            ?: $request->get('billing_pasien')
            ?: $request->get('billing')
            ?: $request->get('billing_id');

        if ($id) {
            $billing = Billing::aktif()->find($id);
            if ($billing) {
                return RegistrasiEpisodeHelper::rootId((int) $billing->registrasi_id);
            }
        }

        if ($request->filled('registrasi_detail_id')) {
            $detail = RegistrasiDetail::find($request->get('registrasi_detail_id'));
            if ($detail) {
                return RegistrasiEpisodeHelper::rootId((int) $detail->registrasi_id);
            }
        }

        if ($request->filled('registrasi_id')) {
            $registrasi = Registrasi::find($request->get('registrasi_id'));
            if ($registrasi) {
                return RegistrasiEpisodeHelper::rootId((int) $registrasi->registrasi_id);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function muatEpisode(int $rootId): array
    {
        $memberIds = RegistrasiEpisodeHelper::memberIds($rootId);

        $registrasis = Registrasi::with('pasien')
            ->whereIn('registrasi_id', $memberIds)
            ->orderBy('tgl_masuk')
            ->orderBy('registrasi_id')
            ->get()
            ->keyBy('registrasi_id');

        $billings = Billing::with([
            'registrasiDetail.bagian', 'kelas', 'nasabah',
            'details.registrasiDetail.bagian',
        ])
            ->aktif()
            ->whereIn('registrasi_id', $memberIds)
            ->orderBy('registrasi_id')
            ->orderBy('billing_id')
            ->get();

        // Pastikan setiap registrasi yang sudah punya billing tetap mendapat
        // administrasi (harga diisi belakangan bila tarif baru tersedia).
        if ($billings->isEmpty()) {
            $detail = RegistrasiDetail::where('registrasi_id', $rootId)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->orderBy('registrasi_detail_id')
                ->first();

            if ($detail) {
                BillingHelper::pastikanBilling($registrasis->get($rootId) ?? Registrasi::findOrFail($rootId), $detail);
                $billings = Billing::with([
                    'registrasiDetail.bagian', 'kelas', 'nasabah',
                    'details.registrasiDetail.bagian',
                ])
                    ->aktif()
                    ->whereIn('registrasi_id', $memberIds)
                    ->orderBy('registrasi_id')
                    ->orderBy('billing_id')
                    ->get();
            }
        }

        foreach ($billings as $billing) {
            if (! $billing->is_terbuka || ! $billing->registrasi) {
                continue;
            }

            BillingHelper::syncAdministrasi($billing->registrasi, $billing->registrasiDetail);
        }

        $billings = Billing::with([
            'registrasi', 'registrasiDetail.bagian', 'kelas', 'nasabah',
            'details.registrasiDetail.bagian',
        ])
            ->aktif()
            ->whereIn('registrasi_id', $memberIds)
            ->orderBy('registrasi_id')
            ->orderBy('billing_id')
            ->get();

        foreach ($billings as $index => $billing) {
            $billings[$index] = BillingHelper::hitungTotal($billing)->load([
                'registrasi', 'registrasiDetail.bagian', 'kelas', 'nasabah',
                'details.registrasiDetail.bagian',
            ]);
        }

        $detailIds = $billings
            ->pluck('registrasi_detail_id')
            ->merge($billings->flatMap(fn (Billing $billing) => $billing->details->pluck('registrasi_detail_id')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $details = RegistrasiDetail::with('bagian')
            ->whereIn('registrasi_detail_id', $detailIds)
            ->get()
            ->keyBy('registrasi_detail_id');

        $sections = [];

        $pastikanSection = function (int $detailId) use (&$sections, $details, $registrasis): void {
            if (isset($sections[$detailId])) {
                return;
            }

            $detail = $details->get($detailId);
            $registrasi = $detail ? $registrasis->get((int) $detail->registrasi_id) : null;

            $sections[$detailId] = [
                'detail_id' => $detailId,
                'detail' => $detail,
                'registrasi' => $registrasi,
                'nama_bagian' => $detail?->bagian?->nama_bagian
                    ?? ($detailId > 0 ? 'Layanan #'.$detailId : 'Tanpa Detail Layanan'),
                'items' => collect(),
                'billings' => collect(),
                'billing_ids' => [],
            ];
        };

        foreach ($billings as $billing) {
            $headerDetailId = (int) ($billing->registrasi_detail_id ?? 0);
            if ($headerDetailId > 0) {
                $pastikanSection($headerDetailId);
                $this->tempelBilling($sections[$headerDetailId], $billing);
            }

            foreach ($billing->details as $item) {
                if ((int) $item->status !== BillingDetail::POSTED) {
                    continue;
                }

                $detailId = (int) ($item->registrasi_detail_id ?: $headerDetailId);
                $pastikanSection($detailId);
                $sections[$detailId]['items']->push($item);
                $this->tempelBilling($sections[$detailId], $billing);
            }
        }

        $sections = collect($sections)
            ->filter(fn (array $section) => $section['items']->isNotEmpty() || $section['billings']->isNotEmpty())
            ->map(function (array $section) {
                $section['total'] = (float) $section['items']->sum('subtotal');
                $section['billing_terbuka'] = $section['billings']
                    ->filter(fn (Billing $billing) => $billing->is_terbuka && (int) $billing->registrasi_detail_id > 0)
                    ->values();

                return $section;
            })
            ->sortBy(function (array $section) {
                $registrasi = $section['registrasi'];
                $detail = $section['detail'];

                return implode('|', [
                    $registrasi?->tgl_masuk ?? '',
                    str_pad((string) ($registrasi?->registrasi_id ?? 0), 10, '0', STR_PAD_LEFT),
                    $detail?->tgl_daftar ?? '',
                    str_pad((string) $section['detail_id'], 10, '0', STR_PAD_LEFT),
                ]);
            })
            ->values();

        return [
            'registrasi' => $registrasis->get($rootId),
            'registrasis' => $registrasis,
            'billings' => $billings,
            'sections' => $sections,
            'totalSubtotal' => (float) $billings->sum('subtotal'),
            'totalDiskon' => (float) $billings->sum('diskon'),
            'totalBilling' => (float) $billings->sum('total'),
        ];
    }

    /**
     * @param  array<string, mixed>  $section
     */
    private function tempelBilling(array &$section, Billing $billing): void
    {
        if (in_array((int) $billing->billing_id, $section['billing_ids'], true)) {
            return;
        }

        $section['billing_ids'][] = (int) $billing->billing_id;
        $section['billings']->push($billing);
    }

    public function store(Request $request, $billing = null)
    {
        BillingAkses::authorize(BillingAkses::BILLING_PASIEN);

        $billingId = $billing ?: $request->input('billing_id');
        abort_if(! $billingId, 422, 'Billing tidak dipilih.');

        $data = $request->validate([
            'item' => ['required', 'string', 'regex:/^(TINDAKAN|BMHP|OBAT):[0-9]+$/'],
            'qty' => 'required|numeric|min:0.01',
            'registrasi_detail_id' => 'required|integer|exists:registrasi_detail,registrasi_detail_id',
        ]);

        $billingModel = Billing::aktif()->findOrFail($billingId);
        abort_if(! $billingModel->is_terbuka, 422, 'Billing sudah ditutup atau dibatalkan.');
        abort_if((int) $data['registrasi_detail_id'] !== (int) $billingModel->registrasi_detail_id, 422, 'Detail registrasi tidak sesuai dengan billing.');

        $detail = RegistrasiDetail::where('registrasi_detail_id', $billingModel->registrasi_detail_id)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->firstOrFail();

        [$jenis, $itemId] = explode(':', $data['item']);
        $item = $this->resolveItem($billingModel, $jenis, (int) $itemId);
        $item['kode_unik'] = 'BILLING:'.uniqid('', true);
        $item['registrasi_detail_id'] = $detail->registrasi_detail_id;
        $item['qty'] = $data['qty'];
        $item['bagian_id'] = $detail->bagian_id;
        $item['kelas_ruang_id'] = $this->kelasTarif($billingModel);

        BillingHelper::postDetail($billingModel, $item);

        return back()->with('success', 'Item billing ditambahkan.');
    }

    public function harga(Request $request)
    {
        BillingAkses::authorize(BillingAkses::BILLING_PASIEN);

        $data = $request->validate([
            'billing_id' => 'required|integer|exists:billing,billing_id',
            'jenis' => 'required|in:TINDAKAN,BMHP,OBAT',
            'item_id' => 'required|integer|min:1',
        ]);

        $billing = Billing::aktif()->findOrFail($data['billing_id']);
        $item = $this->resolveItem($billing, $data['jenis'], (int) $data['item_id']);

        return response()->json([
            'harga' => (float) $item['harga_satuan'],
            'satuan' => $item['satuan'],
            'nama' => $item['nama'],
        ]);
    }

    private function resolveItem(Billing $billing, string $jenis, int $itemId): array
    {
        $kelasRuangId = $this->kelasTarif($billing);

        if ($jenis === 'TINDAKAN') {
            $tindakan = Tindakan::aktif()->findOrFail($itemId);
            $tarif = TarifHelper::tindakan($itemId, $kelasRuangId);

            return [
                'jenis_layanan' => 'TINDAKAN',
                'sumber_type' => 'TINDAKAN',
                'sumber_id' => $itemId,
                'kode' => $tindakan->kode_tindakan,
                'nama' => $tindakan->nama_tindakan,
                'satuan' => null,
                'harga_satuan' => (float) $tarif['tarif'],
                'harga_beli' => 0,
                'jasa_sarana' => (float) $tarif['jasa_sarana'],
                'jasa_medis' => (float) $tarif['jasa_medis'],
                'bhp' => (float) $tarif['bhp'],
                'kelas_ruang_id' => $kelasRuangId,
            ];
        }

        $barang = Barang::with(['jenis', 'satuan'])->aktif()->findOrFail($itemId);
        $jenisBarang = strtolower((string) $barang->jenis?->nama_jenis_barang);
        $harusnya = $jenis === 'OBAT' ? 'obat' : 'bmhp';
        abort_if($jenisBarang !== $harusnya, 422, 'Item bukan '.$harusnya.'.');

        $tarif = TarifHelper::barang(
            $itemId,
            null,
            $billing->registrasiDetail?->bagian_id
        );

        return [
            'jenis_layanan' => $jenis,
            'sumber_type' => 'BARANG',
            'sumber_id' => $itemId,
            'kode' => $barang->kode_barang,
            'nama' => $barang->nama_barang,
            'satuan' => $barang->textSatuan(),
            'harga_satuan' => (float) $tarif['harga_jual'],
            'harga_beli' => (float) $tarif['harga_beli'],
            'jasa_sarana' => 0,
            'jasa_medis' => 0,
            'bhp' => 0,
            'kelas_ruang_id' => $kelasRuangId,
        ];
    }

    private function kelasTarif(Billing $billing): ?int
    {
        $kelasDetail = $billing->registrasiDetail
            ? BillingHelper::kelasRuangId($billing->registrasiDetail)
            : null;

        return $kelasDetail ?: ($billing->kelas_ruang_id ? (int) $billing->kelas_ruang_id : null);
    }

    public function voidItem(Request $request, $billing, $detail)
    {
        BillingAkses::authorize(BillingAkses::BILLING_PASIEN);

        $alasan = $request->validate(['alasan' => 'required|string|max:500'])['alasan'];
        $induk = Billing::aktif()->findOrFail($billing);
        abort_if(! $induk->is_terbuka, 422, 'Billing sudah ditutup atau dibatalkan.');
        $row = BillingDetail::where('billing_id', $billing)->aktif()->findOrFail($detail);
        BillingHelper::voidDetail($row, $alasan);

        return back()->with('success', 'Item billing dibatalkan.');
    }

    public function diskon(Request $request, $billing)
    {
        BillingAkses::authorize(BillingAkses::BILLING_PASIEN);

        $data = $request->validate(['diskon' => 'required|numeric|min:0']);
        $row = Billing::aktif()->findOrFail($billing);
        abort_if(! $row->is_terbuka, 422, 'Billing sudah ditutup atau dibatalkan.');

        $row->update(['diskon' => $data['diskon'], 'mod_time' => now(), 'mod_user_id' => auth()->id()]);
        BillingHelper::hitungTotal($row);

        return back()->with('success', 'Diskon billing diperbarui.');
    }

    public function tutup(Request $request, $billing)
    {
        BillingAkses::authorize(BillingAkses::BILLING_PASIEN);

        $row = Billing::aktif()->findOrFail($billing);

        try {
            $row = BillingHelper::tutup($row);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Billing '.$row->no_billing.' ditutup.');
    }

    public function tutupSemua(Request $request)
    {
        BillingAkses::authorize(BillingAkses::BILLING_PASIEN);

        $data = $request->validate([
            'registrasi_id' => 'required|integer|exists:registrasi,registrasi_id',
        ]);

        $rootId = RegistrasiEpisodeHelper::rootId((int) $data['registrasi_id']);
        $memberIds = RegistrasiEpisodeHelper::memberIds($rootId);

        $billings = Billing::aktif()
            ->whereIn('registrasi_id', $memberIds)
            ->where('status_billing', Billing::OPEN)
            ->orderBy('billing_id')
            ->get();

        if ($billings->isEmpty()) {
            return back()->with('error', 'Tidak ada billing terbuka pada episode ini.');
        }

        $berhasil = 0;
        $gagal = [];

        foreach ($billings as $billing) {
            try {
                BillingHelper::tutup($billing);
                $berhasil++;
            } catch (RuntimeException $e) {
                $gagal[] = ($billing->no_billing ?: 'Draft #'.$billing->billing_id).' ('.$e->getMessage().')';
            }
        }

        if ($gagal !== []) {
            return back()->with(
                'error',
                $berhasil.' billing ditutup. Gagal: '.implode('; ', $gagal).'.'
            );
        }

        return back()->with('success', $berhasil.' billing pada episode ini berhasil ditutup.');
    }

    public function buka(Request $request, $billing)
    {
        BillingAkses::authorize(BillingAkses::BILLING_PASIEN);

        $row = Billing::aktif()->findOrFail($billing);

        try {
            BillingHelper::buka($row);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Billing '.$row->no_billing.' dibuka kembali.');
    }

    public function bukaSemua(Request $request)
    {
        BillingAkses::authorize(BillingAkses::BILLING_PASIEN);

        $data = $request->validate([
            'registrasi_id' => 'required|integer|exists:registrasi,registrasi_id',
        ]);

        $rootId = RegistrasiEpisodeHelper::rootId((int) $data['registrasi_id']);
        $memberIds = RegistrasiEpisodeHelper::memberIds($rootId);

        $billings = Billing::aktif()
            ->whereIn('registrasi_id', $memberIds)
            ->where('status_billing', Billing::CLOSED)
            ->orderBy('billing_id')
            ->get();

        if ($billings->isEmpty()) {
            return back()->with('error', 'Tidak ada billing tertutup pada episode ini.');
        }

        foreach ($billings as $billing) {
            BillingHelper::buka($billing);
        }

        return back()->with('success', $billings->count().' billing pada episode ini berhasil dibuka.');
    }

    public function void(Request $request, $billing)
    {
        BillingAkses::authorize(BillingAkses::BILLING_PASIEN);

        $alasan = $request->validate(['alasan' => 'required|string|max:500'])['alasan'];
        $row = Billing::aktif()->findOrFail($billing);
        abort_if(! $row->is_terbuka, 422, 'Buka billing sebelum membatalkan.');

        try {
            BillingHelper::void($row, $alasan);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Billing dibatalkan.');
    }

    public function cetakEpisode(Request $request)
    {
        BillingAkses::authorizeAny([BillingAkses::BILLING_PASIEN, BillingAkses::DAFTAR_BILLING]);

        $rootId = $this->resolveEpisodeRoot($request);
        abort_if(! $rootId, 404, 'Episode billing tidak ditemukan.');

        $root = Registrasi::with('pasien')->findOrFail($rootId);
        $memberIds = RegistrasiEpisodeHelper::memberIds($rootId);

        $billings = $this->billingUntukCetak()
            ->whereIn('registrasi_id', $memberIds)
            ->get();

        return $this->tampilkanCetak($root->pasien, $billings, 'Billing Episode EP-'.$rootId);
    }

    public function cetakPasien(Request $request)
    {
        BillingAkses::authorizeAny([BillingAkses::BILLING_PASIEN, BillingAkses::DAFTAR_BILLING]);

        $data = $request->validate([
            'pasien_id' => 'required|integer|exists:pasien,pasien_id',
        ]);

        $pasien = Pasien::findOrFail($data['pasien_id']);

        $billings = $this->billingUntukCetak()
            ->where('pasien_id', $pasien->pasien_id)
            ->get();

        return $this->tampilkanCetak($pasien, $billings, 'Seluruh Billing Pasien');
    }

    private function billingUntukCetak()
    {
        return Billing::with([
            'pasien', 'registrasi', 'registrasiDetail.bagian',
            'details.registrasiDetail.bagian', 'kelas', 'nasabah',
        ])
            ->aktif()
            ->where('status_billing', '!=', Billing::VOID)
            ->orderBy('registrasi_id')
            ->orderBy('billing_id');
    }

    private function tampilkanCetak(?Pasien $pasien, $billings, string $judul)
    {
        $billings = $billings
            ->filter(fn (Billing $billing) => (int) $billing->status_billing !== Billing::VOID)
            ->values();

        $totalSubtotal = (float) $billings->sum('subtotal');
        $totalDiskon = (float) $billings->sum('diskon');
        $totalTotal = (float) $billings->sum('total');

        return view('moduls.Billing.Transaksi.BillingPasien.cetak_gabungan', compact(
            'pasien', 'billings', 'judul', 'totalSubtotal', 'totalDiskon', 'totalTotal'
        ));
    }

    public function cetak(Request $request, $billing)
    {
        BillingAkses::authorizeAny([BillingAkses::BILLING_PASIEN, BillingAkses::DAFTAR_BILLING]);

        $billing = Billing::with([
            'pasien', 'registrasi.pasien', 'registrasiDetail.bagian',
            'details.registrasiDetail.bagian', 'kelas', 'nasabah',
        ])->aktif()->findOrFail($billing);

        $billing = BillingHelper::hitungTotal($billing);
        $registrasi = $billing->registrasi;

        return view('moduls.Billing.Transaksi.BillingPasien.cetak', compact('billing', 'registrasi'));
    }
}
