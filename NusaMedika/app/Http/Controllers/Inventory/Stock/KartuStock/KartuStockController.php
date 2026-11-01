<?php

namespace App\Http\Controllers\Inventory\Stock\KartuStock;

use App\Http\Controllers\Controller;
use App\Models\Bagian;
use App\Models\Barang;
use App\Models\BarangJenis;
use App\Models\KartuStock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class KartuStockController extends Controller
{
    public const JENIS_DETAIL = 'kartu_stock_detail';

    public const JENIS_SUMMARY = 'summary_kartu_stock';

    public const JENIS_REKAP = 'rekapitulasi_summary_kartu_stock';

    public const JENIS_OPTS = [
        self::JENIS_DETAIL => 'Kartu Stock Detail',
        self::JENIS_SUMMARY => 'Summary Kartu Stock',
        self::JENIS_REKAP => 'Rekapitulasi Summary Kartu Stock',
    ];

    public function index(Request $request)
    {
        $data = $this->prepare($request);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return view('moduls.Inventory.Stock.KartuStock.kartu_stock', $data);
    }

    public function cetak(Request $request)
    {
        $data = $this->prepare($request, 100000);

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return view('moduls.Inventory.Stock.KartuStock.kartu_stock_cetak', $data);
    }

    private function prepare(Request $request, int $perPage = 50)
    {
        $jenis = $request->input('jenis', self::JENIS_DETAIL);
        if (! in_array($jenis, array_keys(self::JENIS_OPTS), true)) {
            $jenis = self::JENIS_DETAIL;
        }

        $barangId = $request->input('barang_id');
        if ($jenis === self::JENIS_REKAP) {
            $barangId = null;
        }
        $bagianId = $request->input('bagian_id');
        $jenisBarangId = $request->input('jenis_barang_id');
        $tanggalAwal = $request->input('tanggal_awal');
        $tanggalAkhir = $request->input('tanggal_akhir');
        $jenisBarangBarangIds = [];
        if ($jenis === self::JENIS_REKAP && $jenisBarangId !== null && $jenisBarangId !== '') {
            $jenisBarangBarangIds = Barang::aktif()
                ->where('jenis_barang_id', (int) $jenisBarangId)
                ->pluck('barang_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $runningMap = [];

        $hasFilter = ($barangId !== null && $barangId !== '')
            || ($bagianId !== null && $bagianId !== '')
            || ($tanggalAwal !== null && $tanggalAwal !== '')
            || ($tanggalAkhir !== null && $tanggalAkhir !== '')
            || $jenis === self::JENIS_REKAP;

        if ($hasFilter && ($bagianId === null || $bagianId === '')) {
            return redirect()->route('kartu_stock.index')
                ->with('error', 'Bagian wajib dipilih.')
                ->withInput();
        }

        if ($hasFilter && in_array($jenis, [self::JENIS_DETAIL, self::JENIS_SUMMARY], true)
            && ($barangId === null || $barangId === '')) {
            return redirect()->route('kartu_stock.index')
                ->with('error', 'Barang wajib dipilih untuk Kartu Stock Detail / Summary.')
                ->withInput();
        }

        $base = function () use ($barangId, $bagianId, $tanggalAwal, $tanggalAkhir) {
            return DB::table('kartu_stock')
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->when($barangId !== null && $barangId !== '', fn ($q) => $q->where('barang_id', (int) $barangId))
                ->when(! empty($jenisBarangBarangIds), fn ($q) => $q->whereIn('barang_id', $jenisBarangBarangIds))
                ->when($bagianId !== null && $bagianId !== '', fn ($q) => $q->where('bagian_id', (int) $bagianId))
                ->when($tanggalAwal !== null && $tanggalAwal !== '', fn ($q) => $q->whereDate('tanggal', '>=', $tanggalAwal))
                ->when($tanggalAkhir !== null && $tanggalAkhir !== '', fn ($q) => $q->whereDate('tanggal', '<=', $tanggalAkhir));
        };

        if (! $hasFilter) {
            $kartuList = $this->paginate([], $request);
            $totMasuk = 0;
            $totKeluar = 0;
        } elseif ($jenis === self::JENIS_SUMMARY) {
            $rows = DB::table('kartu_stock as ks')
                ->selectRaw('ks.barang_id, ks.no_batch, b.kode_barang, b.nama_barang, s.singkatan as satuan, COALESCE(SUM(ks.qty_masuk),0) as tot_masuk, COALESCE(SUM(ks.qty_keluar),0) as tot_keluar')
                ->join('barang as b', 'b.barang_id', '=', 'ks.barang_id')
                ->leftJoin('satuan as s', 's.satuan_id', '=', 'b.satuan_id')
                ->where(function ($q) {
                    $q->whereNull('ks.status_batal')->orWhere('ks.status_batal', 0);
                })
                ->when($barangId !== null && $barangId !== '', fn ($q) => $q->where('ks.barang_id', (int) $barangId))
                ->when($bagianId !== null && $bagianId !== '', fn ($q) => $q->where('ks.bagian_id', (int) $bagianId))
                ->when(! empty($jenisBarangBarangIds), fn ($q) => $q->whereIn('ks.barang_id', $jenisBarangBarangIds))
                ->when($tanggalAwal !== null && $tanggalAwal !== '', fn ($q) => $q->whereDate('ks.tanggal', '>=', $tanggalAwal))
                ->when($tanggalAkhir !== null && $tanggalAkhir !== '', fn ($q) => $q->whereDate('ks.tanggal', '<=', $tanggalAkhir))
                ->groupBy('ks.barang_id', 'ks.no_batch', 'b.kode_barang', 'b.nama_barang', 's.singkatan')
                ->orderBy('b.nama_barang')
                ->orderBy('ks.no_batch')
                ->get();

            $priorBalances = [];
            if ($tanggalAwal !== null && $tanggalAwal !== '') {
                $priorRows = DB::table('kartu_stock')
                    ->selectRaw('barang_id, no_batch, COALESCE(SUM(qty_masuk),0) - COALESCE(SUM(qty_keluar),0) as saldo')
                    ->where(function ($q) {
                        $q->whereNull('status_batal')->orWhere('status_batal', 0);
                    })
                    ->when($barangId !== null && $barangId !== '', fn ($q) => $q->where('barang_id', (int) $barangId))
                    ->when($bagianId !== null && $bagianId !== '', fn ($q) => $q->where('bagian_id', (int) $bagianId))
                    ->whereDate('tanggal', '<', $tanggalAwal)
                    ->groupBy('barang_id', 'no_batch')
                    ->get();

                foreach ($priorRows as $pr) {
                    $priorBalances[(int) $pr->barang_id.'|'.($pr->no_batch ?? '')] = (float) $pr->saldo;
                }
            }

            foreach ($rows as $row) {
                $row->stock_awal = $priorBalances[(int) $row->barang_id.'|'.($row->no_batch ?? '')] ?? 0.0;
            }

            $kartuList = $this->paginate($rows, $request, $perPage);
            $totMasuk = $base()->sum('qty_masuk');
            $totKeluar = $base()->sum('qty_keluar');
        } elseif ($jenis === self::JENIS_REKAP) {
            $rows = DB::table('kartu_stock as ks')
                ->selectRaw('ks.barang_id, COUNT(DISTINCT ks.no_batch) as jumlah_batch, b.kode_barang, b.nama_barang, s.singkatan as satuan, COALESCE(SUM(ks.qty_masuk),0) as tot_masuk, COALESCE(SUM(ks.qty_keluar),0) as tot_keluar')
                ->join('barang as b', 'b.barang_id', '=', 'ks.barang_id')
                ->leftJoin('satuan as s', 's.satuan_id', '=', 'b.satuan_id')
                ->where(function ($q) {
                    $q->whereNull('ks.status_batal')->orWhere('ks.status_batal', 0);
                })
                ->when($bagianId !== null && $bagianId !== '', fn ($q) => $q->where('ks.bagian_id', (int) $bagianId))
                ->when(! empty($jenisBarangBarangIds), fn ($q) => $q->whereIn('ks.barang_id', $jenisBarangBarangIds))
                ->when($tanggalAwal !== null && $tanggalAwal !== '', fn ($q) => $q->whereDate('ks.tanggal', '>=', $tanggalAwal))
                ->when($tanggalAkhir !== null && $tanggalAkhir !== '', fn ($q) => $q->whereDate('ks.tanggal', '<=', $tanggalAkhir))
                ->groupBy('ks.barang_id', 'b.kode_barang', 'b.nama_barang', 's.singkatan')
                ->orderBy('b.nama_barang')
                ->get();

            // Stock awal = saldo sebelum rentang tanggal (per barang), agar
            // Stock Akhir = Stock Awal + Masuk - Keluar.
            $priorBalances = [];
            if ($tanggalAwal !== null && $tanggalAwal !== '') {
                $priorRows = DB::table('kartu_stock')
                    ->selectRaw('barang_id, COALESCE(SUM(qty_masuk),0) - COALESCE(SUM(qty_keluar),0) as saldo')
                    ->where(function ($q) {
                        $q->whereNull('status_batal')->orWhere('status_batal', 0);
                    })
                    ->when($barangId !== null && $barangId !== '', fn ($q) => $q->where('barang_id', (int) $barangId))
                    ->when($bagianId !== null && $bagianId !== '', fn ($q) => $q->where('bagian_id', (int) $bagianId))
                    ->when(! empty($jenisBarangBarangIds), fn ($q) => $q->whereIn('barang_id', $jenisBarangBarangIds))
                    ->whereDate('tanggal', '<', $tanggalAwal)
                    ->groupBy('barang_id')
                    ->get();

                foreach ($priorRows as $pr) {
                    $priorBalances[(int) $pr->barang_id] = (float) $pr->saldo;
                }
            }

            foreach ($rows as $row) {
                $row->stock_awal = $priorBalances[(int) $row->barang_id] ?? 0.0;
            }

            $kartuList = $this->paginate($rows, $request, $perPage);
            $totMasuk = $base()->sum('qty_masuk');
            $totKeluar = $base()->sum('qty_keluar');
        } else {
            $query = KartuStock::aktif()->with([
                'barang.satuan',
                'bagian',
                'penerimaanDetail.penerimaan',
                'mutasiBarangDetail.mutasiBarang',
            ])->orderBy('tanggal')->orderBy('kartu_stock_id');

            if ($barangId !== null && $barangId !== '') {
                $query->where('barang_id', (int) $barangId);
            }

            if ($bagianId !== null && $bagianId !== '') {
                $query->where('bagian_id', (int) $bagianId);
            }

            if ($tanggalAwal !== null && $tanggalAwal !== '') {
                $query->whereDate('tanggal', '>=', $tanggalAwal);
            }

            if ($tanggalAkhir !== null && $tanggalAkhir !== '') {
                $query->whereDate('tanggal', '<=', $tanggalAkhir);
            }

            // Transaksi per item barang: gabungkan baris batch yang merupakan
            // event transaksi yang sama (barang/bagian/tanggal/jenis mutasi +
            // referensi + keterangan) menjadi satu baris, menjumlahkan qty.
            // Pencatatan di DB tetap per id_batch.
            $allRows = $query->get();

            $grouped = [];
            foreach ($allRows as $row) {
                $key = implode('|', [
                    (string) $row->barang_id,
                    (string) $row->bagian_id,
                    (string) $row->tanggal,
                    (string) $row->jenis_mutasi,
                    (string) ($row->ref_penerimaan_detail_id ?? ''),
                    (string) ($row->ref_mutasi_barang_detail_id ?? ''),
                    (string) ($row->ref_peresepan_obat_detail_id ?? ''),
                    (string) ($row->ref_emr_id ?? ''),
                    (string) ($row->keterangan ?? ''),
                ]);

                if (! isset($grouped[$key])) {
                    $grouped[$key] = $row;
                } else {
                    $grouped[$key]->qty_masuk = (float) $grouped[$key]->qty_masuk + (float) $row->qty_masuk;
                    $grouped[$key]->qty_keluar = (float) $grouped[$key]->qty_keluar + (float) $row->qty_keluar;
                }
            }

            $aggregated = array_values($grouped);

            $totMasuk = $base()->sum('qty_masuk');
            $totKeluar = $base()->sum('qty_keluar');

            $runningMap = $this->runningBalancePerBarangAggregated($aggregated);

            $kartuList = $this->paginate($aggregated, $request, $perPage);
        }

        $barangList = Barang::aktif()->with('satuan')->orderBy('nama_barang')->get();
        $bagianList = Bagian::aktif()->orderBy('nama_bagian')->get();
        $jenisBarangList = BarangJenis::aktif()->orderBy('nama_jenis_barang')->get();
        $jenisOpts = self::JENIS_OPTS;

        return compact(
            'jenis', 'jenisOpts', 'kartuList', 'barangId', 'bagianId', 'tanggalAwal', 'tanggalAkhir',
            'totMasuk', 'totKeluar', 'runningMap', 'hasFilter', 'barangList', 'bagianList', 'jenisBarangList', 'jenisBarangId'
        );
    }

    private function runningBalancePerBarang(?string $barangId, ?string $bagianId): array
    {
        $rows = DB::table('kartu_stock')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->when($barangId !== null && $barangId !== '', fn ($q) => $q->where('barang_id', (int) $barangId))
            ->when($bagianId !== null && $bagianId !== '', fn ($q) => $q->where('bagian_id', (int) $bagianId))
            ->orderBy('tanggal')->orderBy('kartu_stock_id')
            ->get(['kartu_stock_id', 'barang_id', 'qty_masuk', 'qty_keluar']);

        $saldoPerBarang = [];
        $map = [];

        foreach ($rows as $r) {
            $sebelum = (float) ($saldoPerBarang[$r->barang_id] ?? 0);
            $sesudah = $sebelum + (float) $r->qty_masuk - (float) $r->qty_keluar;
            $map[$r->kartu_stock_id] = [$sebelum, $sesudah];
            $saldoPerBarang[$r->barang_id] = $sesudah;
        }

        return $map;
    }

    private function runningBalancePerBarangAggregated(array $aggregated): array
    {
        $saldoPerBarang = [];
        $map = [];

        foreach ($aggregated as $row) {
            $sebelum = (float) ($saldoPerBarang[$row->barang_id] ?? 0);
            $sesudah = $sebelum + (float) $row->qty_masuk - (float) $row->qty_keluar;
            $map[$row->kartu_stock_id] = [$sebelum, $sesudah];
            $saldoPerBarang[$row->barang_id] = $sesudah;
        }

        return $map;
    }

    private function paginate($rows, Request $request, int $perPage = 50): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();
        $items = collect($rows);
        $paged = $items->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator($paged, $items->count(), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'query' => $request->query(),
        ]);

        $paginator->appends(request()->except(['page']));

        return $paginator;
    }
}
