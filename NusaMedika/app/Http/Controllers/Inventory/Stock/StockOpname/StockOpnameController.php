<?php

namespace App\Http\Controllers\Inventory\Stock\StockOpname;

use App\Helpers\StockHelper;
use App\Http\Controllers\Controller;
use App\Models\Bagian;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Stock Opname: menyesuaikan saldo sistem dengan hasil hitung fisik dan
 * menghapus baris stok (satu per satu maupun seluruh stok pada satu bagian).
 *
 * Semua perubahan saldo melewati `StockHelper` agar tercatat di `kartu_stock`
 * sebagai mutasi "Penyesuaian Stock Opname" (jenis 6).
 */
class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        $bagianId = $request->input('bagian_id');
        $search = trim((string) $request->input('search'));

        $query = Stock::aktif()->with(['barang' => fn ($b) => $b->with('satuan', 'jenis'), 'bagian', 'batchBarang']);

        if ($bagianId !== null && $bagianId !== '') {
            $query->where('bagian_id', (int) $bagianId);
        }

        if ($search !== '') {
            $query->whereHas('barang', function ($q) use ($search) {
                $q->where('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%");
            });
        }

        $stockList = $query->orderBy('bagian_id')
            ->orderBy('barang_id')
            ->orderBy('no_batch')
            ->orderBy('id_batch')
            ->paginate(50)
            ->withQueryString();

        $bagianList = Bagian::aktif()->orderBy('nama_bagian')->get();
        $bagianTerpilih = ($bagianId !== null && $bagianId !== '')
            ? $bagianList->firstWhere('bagian_id', (int) $bagianId)
            : null;

        $totalBagian = $bagianTerpilih
            ? Stock::aktif()->where('bagian_id', (int) $bagianTerpilih->bagian_id)->count()
            : 0;

        return view('moduls.Inventory.Stock.StockOpname.stock_opname', compact(
            'stockList', 'bagianId', 'search', 'bagianList', 'bagianTerpilih', 'totalBagian'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'stock_id' => ['required', 'array', 'min:1'],
            'stock_id.*' => ['required', 'integer'],
            'stock_fisik' => ['required', 'array'],
            'stock_fisik.*' => ['required', 'numeric', 'min:0'],
        ], [
            'stock_id.required' => 'Tidak ada baris stok yang dikirim.',
            'stock_fisik.*.required' => 'Jumlah stok fisik wajib diisi pada setiap baris.',
            'stock_fisik.*.numeric' => 'Jumlah stok fisik harus berupa angka.',
            'stock_fisik.*.min' => 'Jumlah stok fisik tidak boleh negatif.',
        ]);

        $diubah = 0;
        $selisihNaik = 0.0;
        $selisihTurun = 0.0;

        DB::transaction(function () use ($data, &$diubah, &$selisihNaik, &$selisihTurun) {
            foreach ($data['stock_id'] as $index => $stockId) {
                $fisik = $data['stock_fisik'][$index] ?? null;
                if ($fisik === null || $fisik === '') {
                    continue;
                }

                $hasil = StockHelper::sesuaikan((int) $stockId, (float) $fisik, [
                    'keterangan' => 'Stock Opname',
                ]);

                if ($hasil === null) {
                    continue;
                }

                $diubah++;
                $selisih = $hasil['sesudah'] - $hasil['sebelum'];
                $selisih > 0 ? $selisihNaik += $selisih : $selisihTurun += abs($selisih);
            }
        });

        if ($diubah === 0) {
            return redirect()
                ->route('stock_opname.index', $request->only(['bagian_id', 'search']))
                ->with('success', 'Tidak ada penyesuaian. Jumlah fisik sama dengan stok sistem.');
        }

        $pesan = "{$diubah} baris stok disesuaikan.";
        if ($selisihNaik > 0) {
            $pesan .= ' Penambahan '.rtrim(rtrim(number_format($selisihNaik, 2, ',', '.'), '0'), ',').'.';
        }
        if ($selisihTurun > 0) {
            $pesan .= ' Pengurangan '.rtrim(rtrim(number_format($selisihTurun, 2, ',', '.'), '0'), ',').'.';
        }

        return redirect()
            ->route('stock_opname.index', $request->only(['bagian_id', 'search']))
            ->with('success', $pesan);
    }

    public function destroy($stock_opname)
    {
        $stock = Stock::aktif()->find($stock_opname);

        if (! $stock) {
            return redirect()
                ->route('stock_opname.index')
                ->with('error', 'Baris stok tidak ditemukan atau sudah dihapus.');
        }

        $label = ($stock->barang->nama_barang ?? 'Barang').' - batch '.($stock->no_batch ?: '-');

        DB::transaction(function () use ($stock) {
            StockHelper::hapus((int) $stock->stock_id, [
                'keterangan' => 'Hapus stock (Stock Opname)',
            ]);
        });

        return redirect()
            ->route('stock_opname.index', request()->only(['bagian_id', 'search']))
            ->with('success', "Stok {$label} dihapus.");
    }

    /**
     * Hapus seluruh baris stok aktif pada bagian yang dipilih.
     */
    public function hapusSemua(Request $request)
    {
        $data = $request->validate([
            'bagian_id' => ['required', 'integer'],
        ], [
            'bagian_id.required' => 'Pilih bagian/gudang terlebih dahulu.',
        ]);

        $bagian = Bagian::aktif()->find((int) $data['bagian_id']);
        if (! $bagian) {
            return redirect()
                ->route('stock_opname.index')
                ->with('error', 'Bagian/gudang tidak ditemukan atau tidak aktif.');
        }

        $stockIds = Stock::aktif()
            ->where('bagian_id', (int) $bagian->bagian_id)
            ->pluck('stock_id');

        $dihapus = 0;

        DB::transaction(function () use ($stockIds, &$dihapus) {
            foreach ($stockIds as $stockId) {
                if (StockHelper::hapus((int) $stockId, [
                    'keterangan' => 'Hapus semua stock (Stock Opname)',
                ]) !== null) {
                    $dihapus++;
                }
            }
        });

        if ($dihapus === 0) {
            return redirect()
                ->route('stock_opname.index', ['bagian_id' => $bagian->bagian_id])
                ->with('success', "Tidak ada stok aktif pada {$bagian->nama_bagian}.");
        }

        return redirect()
            ->route('stock_opname.index', ['bagian_id' => $bagian->bagian_id])
            ->with('success', "{$dihapus} baris stok pada {$bagian->nama_bagian} dihapus.");
    }
}
