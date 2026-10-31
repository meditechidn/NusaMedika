<?php

namespace App\Http\Controllers\Farmasi\Resep\ListPesananResep;

use App\Helpers\BillingHelper;
use App\Helpers\StockHelper;
use App\Http\Controllers\Controller;
use App\Models\Bagian;
use App\Models\PeresepanObat;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListPesananResepController extends Controller
{
    public function index(Request $request)
    {
        $depoList = Bagian::aktif()
            ->where('referensi_bagian_id', 5)
            ->orderBy('nama_bagian')
            ->get();

        $depoBagianId = (int) session('depo_bagian_id', 0);
        if ($depoBagianId && ! $depoList->contains('bagian_id', $depoBagianId)) {
            $depoBagianId = 0;
        }

        $tglAwal = $request->input('tgl_awal');
        $tglAkhir = $request->input('tgl_akhir');
        $jenisRawat = $request->input('jenis_rawat');
        $noResep = $request->input('no_resep');
        $pasienId = $request->input('pasien_id');
        $status = $request->input('status');

        $hasFilter = $tglAwal || $tglAkhir || $jenisRawat || $noResep || $pasienId || $status !== null && $status !== '';

        $query = PeresepanObat::aktif()
            ->with(['pasien', 'dokter', 'registrasiDetail.bagian', 'registrasiDetail.registrasi', 'details'])
            ->when($status === null || $status === '', fn ($q) => $q->where('status_resep', '!=', 2))
            ->when($status !== null && $status !== '', fn ($q) => $q->where('status_resep', (int) $status));

        if ($hasFilter) {
            if ($tglAwal !== null && $tglAwal !== '') {
                $query->whereDate('tanggal_resep', '>=', $tglAwal);
            }

            if ($tglAkhir !== null && $tglAkhir !== '') {
                $query->whereDate('tanggal_resep', '<=', $tglAkhir);
            }

            if ($jenisRawat !== null && $jenisRawat !== '') {
                $query->whereHas('registrasiDetail.registrasi', function ($rq) use ($jenisRawat) {
                    $rq->where('jenis_rawat', $jenisRawat);
                });
            }

            if ($noResep !== null && $noResep !== '') {
                $query->where('no_resep', 'ilike', '%'.$noResep.'%');
            }

            if ($pasienId !== null && $pasienId !== '') {
                $query->where('pasien_id', (int) $pasienId);
            }
        }

        $resepList = $hasFilter ? $query->orderByDesc('peresepan_obat_id')->paginate(10)->withQueryString() : null;

        return view('moduls.Farmasi.Resep.ListPesananResep.list_pesanan_resep', compact(
            'depoList',
            'depoBagianId',
            'resepList',
            'tglAwal',
            'tglAkhir',
            'jenisRawat',
            'noResep',
            'pasienId',
            'status'
        ));
    }

    public function pilihDepo(Request $request)
    {
        $request->validate([
            'depo_bagian_id' => 'required|integer|exists:bagian,bagian_id',
        ]);

        $depo = Bagian::aktif()
            ->where('bagian_id', $request->input('depo_bagian_id'))
            ->where('referensi_bagian_id', 5)
            ->first();

        if (! $depo) {
            return back()->with('error', 'Depo tidak valid.');
        }

        session()->put('depo_bagian_id', (int) $depo->bagian_id);

        return redirect()->route('list_pesanan_resep.index')->with('success', 'Depo aktif: '.$depo->nama_bagian.'.');
    }

    public function detail($peresepanObat)
    {
        $resep = PeresepanObat::aktif()
            ->with(['pasien', 'dokter', 'registrasiDetail.bagian', 'registrasiDetail.registrasi', 'details.barang'])
            ->findOrFail($peresepanObat);

        return view('moduls.Farmasi.Resep.ListPesananResep.detail', compact('resep'));
    }

    public function cetakTiket($peresepanObat)
    {
        $resep = PeresepanObat::aktif()
            ->with(['pasien', 'dokter', 'registrasiDetail.bagian', 'registrasiDetail.registrasi', 'details.barang'])
            ->findOrFail($peresepanObat);

        return view('moduls.Farmasi.Resep.ListPesananResep.cetak_tiket', compact('resep'));
    }

    public function cetakDetail($peresepanObat)
    {
        $resep = PeresepanObat::aktif()
            ->with(['pasien', 'dokter', 'registrasiDetail.bagian', 'registrasiDetail.registrasi', 'details.barang'])
            ->findOrFail($peresepanObat);

        return view('moduls.Farmasi.Resep.ListPesananResep.cetak_detail', compact('resep'));
    }

    public function dispense($peresepanObat)
    {
        $depoBagianId = (int) session('depo_bagian_id', 0);
        $depo = Bagian::aktif()->where('referensi_bagian_id', 5)->find($depoBagianId ?? 0);

        if (! $depo) {
            return redirect()->route('list_pesanan_resep.index')->with('error', 'Pilih depo terlebih dahulu.');
        }

        $resep = PeresepanObat::aktif()
            ->with(['pasien', 'dokter', 'registrasiDetail.registrasi', 'details.barang'])
            ->findOrFail($peresepanObat);

        if ((int) $resep->status_resep === 1 || (int) $resep->status_resep === 2) {
            return redirect()->route('list_pesanan_resep.detail', $resep->peresepan_obat_id)
                ->with('error', 'Resep '.$resep->no_resep.' sudah diproses.');
        }

        $stocks = Stock::aktif()
            ->with('barang')
            ->where('bagian_id', $depo->bagian_id)
            ->where('jumlah', '>', 0)
            ->get()
            ->groupBy('barang_id');

        return view('moduls.Farmasi.Resep.ListPesananResep.dispense', compact('resep', 'depo', 'stocks'));
    }

    public function dispenseStore(Request $request, $peresepanObat)
    {
        $depoBagianId = (int) session('depo_bagian_id', 0);
        $depo = Bagian::aktif()->where('referensi_bagian_id', 5)->find($depoBagianId ?? 0);

        if (! $depo) {
            return redirect()->route('list_pesanan_resep.index')->with('error', 'Pilih depo terlebih dahulu.');
        }

        $resep = PeresepanObat::aktif()
            ->with(['details' => fn ($q) => $q->aktif()])
            ->findOrFail($peresepanObat);

        if ((int) $resep->status_resep === 1 || (int) $resep->status_resep === 2) {
            return back()->with('error', 'Resep '.$resep->no_resep.' sudah diproses.');
        }

        $batchSel = (array) $request->input('no_batch', []);
        $jumlahSel = (array) $request->input('jumlah_dispense', []);
        $selesai = (bool) $request->input('selesai', false);
        $statusAwal = (int) $resep->status_resep;
        $user = Auth::user();

        DB::beginTransaction();
        try {
            $dispensedApaApa = false;

            foreach ($resep->details as $i => $detail) {
                $selectedBatch = isset($batchSel[$detail->peresepan_obat_detail_id]) ? trim((string) $batchSel[$detail->peresepan_obat_detail_id]) : '';
                $jumlah = (float) ($jumlahSel[$detail->peresepan_obat_detail_id] ?? 0);

                // Item tanpa batch dipilih di-skip (dispense parsial).
                if ($selectedBatch === '') {
                    continue;
                }

                $sisaBelum = (float) $detail->jumlah - (float) ($detail->jumlah_dispense ?? 0);
                if ($jumlah <= 0 || $jumlah > $sisaBelum) {
                    throw new \RuntimeException('Jumlah dispense tidak valid untuk "'.($detail->barang->nama_barang ?? '#'.$detail->barang_id).'".');
                }

                if (str_contains($selectedBatch, '||')) {
                    [$selectedBatch, $hargaPart] = explode('||', $selectedBatch, 2);
                    $selectedBatch = trim($selectedBatch);
                }

                $stock = Stock::aktif()
                    ->where('bagian_id', $depo->bagian_id)
                    ->where('barang_id', $detail->barang_id)
                    ->where('no_batch', $selectedBatch)
                    ->where('jumlah', '>', 0)
                    ->first();

                if (! $stock || (float) $stock->jumlah < $jumlah) {
                    throw new \RuntimeException('Stock batch '.$selectedBatch.' tidak mencukupi untuk "'.($detail->barang->nama_barang ?? '#'.$detail->barang_id).'".');
                }

                $detail->flag_dispense = 1;
                $detail->jumlah_dispense = (float) ($detail->jumlah_dispense ?? 0) + $jumlah;
                $detail->no_batch = $selectedBatch;
                $detail->bagian_dispense_id = $depo->bagian_id;
                $detail->waktu_dispense = now();
                $detail->mod_time = now();
                $detail->mod_user_id = $user->user_id ?? null;
                $detail->save();

                StockHelper::tambahKeluar(
                    $depo->bagian_id,
                    $detail->barang_id,
                    $selectedBatch,
                    $jumlah,
                    [
                        'jenis_mutasi' => 5,
                        'ref_peresepan_obat_detail_id' => $detail->peresepan_obat_detail_id,
                        'tanggal' => now(),
                        'keterangan' => 'Dispense Obat untuk resep '.$resep->no_resep,
                        'harga_beli' => $stock->harga_beli !== null ? (float) $stock->harga_beli : null,
                        'harga_jual' => $stock->harga_jual !== null ? (float) $stock->harga_jual : null,
                        'tgl_expired' => $stock->tgl_expired,
                    ]
                );

                BillingHelper::syncResepDispense((int) $detail->peresepan_obat_detail_id);

                $dispensedApaApa = true;
            }

            // Status resep:
            // - centang Selesai            -> Selesai (1), meski meneruskan resep yang
            //                                 sudah dispense penuh tapi belum ditegaskan.
            // - ada item ter-dispense baru  -> Dispense Sebagian (3)
            // - tanpa dispense baru         -> Menunggu (0) bila baru, tetap (3) bila sudah
            //                                 dispense sebagian sebelumnya.
            if ($selesai) {
                $resep->status_resep = 1;
            } elseif ($dispensedApaApa) {
                $resep->status_resep = 3;
            } else {
                $resep->status_resep = $statusAwal === 3 ? 3 : 0;
            }
            $resep->mod_time = now();
            $resep->mod_user_id = $user->user_id ?? null;
            $resep->save();

            DB::commit();

            return redirect()->route('list_pesanan_resep.detail', $resep->peresepan_obat_id)
                ->with('success', 'Dispense resep '.$resep->no_resep.' dari '.$depo->nama_bagian.' berhasil dicatat.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal dispense obat: '.$e->getMessage())->withInput();
        }
    }

    public function batal($peresepanObat)
    {
        $resep = PeresepanObat::aktif()->findOrFail($peresepanObat);

        if ((int) $resep->status_resep !== 0) {
            return back()->with('error', 'Hanya resep dengan status Menunggu yang dapat dibatalkan.');
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $resep->status_resep = 2;
            $resep->mod_time = now();
            $resep->mod_user_id = $user->user_id ?? null;
            $resep->save();

            DB::commit();

            return redirect()->route('list_pesanan_resep.index')->with('success', 'Resep '.$resep->no_resep.' dibatalkan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal membatalkan resep: '.$e->getMessage());
        }
    }
}
