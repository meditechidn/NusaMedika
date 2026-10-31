<?php

namespace App\Http\Controllers\Inventory\Pesanan\BuatPesanan;

use App\Http\Controllers\Controller;
use App\Models\Bagian;
use App\Models\Barang;
use App\Models\Pemesanan;
use App\Models\PemesananDetail;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BuatPesananController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $search = trim((string) $request->input('search'));

        $query = Pemesanan::aktif()->with('bagian', 'details');

        if ($status !== null && $status !== '') {
            $query->where('status_pemesanan', (int) $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('no_pemesanan', 'ilike', "%{$search}%")
                    ->orWhereHas('details.supplier', fn ($s) => $s->where('nama_supplier', 'ilike', "%{$search}%"));
            });
        }

        $pemesananList = $query->orderByDesc('pemesanan_id')->paginate(10)->withQueryString();

        return view('moduls.Inventory.Pesanan.BuatPesanan.buat_pesanan', compact('pemesananList', 'status', 'search'));
    }

    public function create()
    {
        [$barangSupplierMap, $supplierDistributorMap] = $this->relasiSupplierDistributor();
        $suppliers = Supplier::aktif()->where('jenis_supplier', 'SUPPLIER')->orderBy('nama_supplier')->get();
        $distributors = Supplier::aktif()->where('jenis_supplier', 'DISTRIBUTOR')->orderBy('nama_supplier')->get();
        $gudangs = $this->gudangOptions();
        $barangs = Barang::aktif()->with('satuan')->orderBy('nama_barang')->get();

        return view('moduls.Inventory.Pesanan.BuatPesanan.buat_pesanan_create', compact('suppliers', 'distributors', 'gudangs', 'barangs', 'barangSupplierMap', 'supplierDistributorMap'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $items = $this->validatedItems($request);

        if (empty($items)) {
            return back()->with('error', 'Minimal satu item barang wajib diisi.')->withInput();
        }

        DB::beginTransaction();
        try {
            $pemesanan = new Pemesanan;
            $pemesanan->bagian_id = $data['bagian_id'];
            $pemesanan->tanggal_pemesanan = $data['tanggal_pemesanan'];
            $pemesanan->status_pemesanan = 0;
            $pemesanan->keterangan = $data['keterangan'];
            $pemesanan->input_time = now();
            $pemesanan->input_user_id = Auth::id();
            $pemesanan->status_batal = 0;
            $pemesanan->save();

            $pemesanan->no_pemesanan = 'PO-'.now()->format('Ymd').'-'.str_pad($pemesanan->pemesanan_id, 4, '0', STR_PAD_LEFT);
            $pemesanan->save();

            $this->simpanDetail($pemesanan->pemesanan_id, $items);

            DB::commit();

            return redirect()->route('buat_pesanan.index')->with('success', 'Pemesanan berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyimpan pemesanan: '.$e->getMessage())->withInput();
        }
    }

    public function edit($pemesanan)
    {
        $pemesanan = Pemesanan::aktif()->with(['details' => fn ($q) => $q->aktif()->with('barang.satuan', 'supplier', 'distributor'), 'bagian'])->findOrFail($pemesanan);

        if ((int) $pemesanan->status_pemesanan !== 0) {
            return redirect()->route('buat_pesanan.index')->with('error', 'Pemesanan sudah diproses, tidak dapat diubah.');
        }

        [$barangSupplierMap, $supplierDistributorMap] = $this->relasiSupplierDistributor();
        $suppliers = Supplier::aktif()->where('jenis_supplier', 'SUPPLIER')->orderBy('nama_supplier')->get();
        $distributors = Supplier::aktif()->where('jenis_supplier', 'DISTRIBUTOR')->orderBy('nama_supplier')->get();
        $gudangs = $this->gudangOptions();
        $barangs = Barang::aktif()->with('satuan')->orderBy('nama_barang')->get();

        return view('moduls.Inventory.Pesanan.BuatPesanan.buat_pesanan_edit', compact('pemesanan', 'suppliers', 'distributors', 'gudangs', 'barangs', 'barangSupplierMap', 'supplierDistributorMap'));
    }

    public function update(Request $request, $pemesanan)
    {
        $data = $this->validated($request);
        $items = $this->validatedItems($request);

        $pemesanan = Pemesanan::aktif()->findOrFail($pemesanan);

        if ((int) $pemesanan->status_pemesanan !== 0) {
            return back()->with('error', 'Pemesanan sudah diproses, tidak dapat diubah.');
        }

        if (empty($items)) {
            return back()->with('error', 'Minimal satu item barang wajib diisi.')->withInput();
        }

        DB::beginTransaction();
        try {
            $pemesanan->bagian_id = $data['bagian_id'];
            $pemesanan->tanggal_pemesanan = $data['tanggal_pemesanan'];
            $pemesanan->keterangan = $data['keterangan'];
            $pemesanan->mod_time = now();
            $pemesanan->mod_user_id = Auth::id();
            $pemesanan->save();

            PemesananDetail::where('pemesanan_id', $pemesanan->pemesanan_id)
                ->where(fn ($q) => $q->whereNull('status_batal')->orWhere('status_batal', 0))
                ->update([
                    'status_batal' => 1,
                    'mod_time' => now(),
                    'mod_user_id' => Auth::id(),
                ]);

            $this->simpanDetail($pemesanan->pemesanan_id, $items);

            DB::commit();

            return redirect()->route('buat_pesanan.index')->with('success', 'Pemesanan berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memperbarui pemesanan: '.$e->getMessage())->withInput();
        }
    }

    public function batal(Request $request, $pemesanan)
    {
        DB::beginTransaction();
        try {
            $pemesanan = Pemesanan::aktif()->findOrFail($pemesanan);

            if ((int) $pemesanan->status_pemesanan !== 0) {
                throw new \RuntimeException('Pemesanan sudah diproses, tidak dapat dibatalkan.');
            }

            $pemesanan->status_pemesanan = 3;
            $pemesanan->mod_time = now();
            $pemesanan->mod_user_id = Auth::id();
            $pemesanan->save();

            DB::commit();

            return redirect()->route('buat_pesanan.index')->with('success', 'Pemesanan dibatalkan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal membatalkan pemesanan: '.$e->getMessage());
        }
    }

    public function destroy($pemesanan)
    {
        DB::beginTransaction();
        try {
            $pemesanan = Pemesanan::aktif()->findOrFail($pemesanan);

            if ((int) $pemesanan->status_pemesanan !== 0) {
                throw new \RuntimeException('Pemesanan sudah diproses, tidak dapat dihapus.');
            }

            PemesananDetail::where('pemesanan_id', $pemesanan->pemesanan_id)
                ->where(fn ($q) => $q->whereNull('status_batal')->orWhere('status_batal', 0))
                ->update([
                    'status_batal' => 1,
                    'mod_time' => now(),
                    'mod_user_id' => Auth::id(),
                ]);

            $pemesanan->status_batal = 1;
            $pemesanan->mod_time = now();
            $pemesanan->mod_user_id = Auth::id();
            $pemesanan->save();

            DB::commit();

            return redirect()->route('buat_pesanan.index')->with('success', 'Pemesanan berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus pemesanan: '.$e->getMessage());
        }
    }

    public function cetak($pemesanan)
    {
        $pemesanan = Pemesanan::with([
            'bagian',
            'details' => fn ($q) => $q->aktif()->with(['barang.satuan', 'supplier', 'distributor']),
        ])->findOrFail($pemesanan);

        return view('moduls.Inventory.Pesanan.BuatPesanan.surat_pesanan', compact('pemesanan'));
    }

    private function simpanDetail(int $pemesananId, array $items): void
    {
        foreach ($items as $item) {
            $harga = $item['harga_beli'] ?? 0;

            $detail = new PemesananDetail;
            $detail->pemesanan_id = $pemesananId;
            $detail->barang_id = $item['barang_id'];
            $detail->supplier_id = $item['supplier_id'] ?? null;
            $detail->distributor_id = $item['distributor_id'] ?? null;
            $detail->jumlah_pesan = $item['jumlah_pesan'];
            $detail->harga_beli = $harga;
            $detail->harga_jual = $item['harga_jual'] ?? null;
            $detail->subtotal = round((float) $item['jumlah_pesan'] * (float) $harga, 2);
            $detail->input_time = now();
            $detail->input_user_id = Auth::id();
            $detail->status_batal = 0;
            $detail->save();
        }
    }

    private function relasiSupplierDistributor(): array
    {
        $barangSupplier = DB::table('barang_supplier')
            ->where(fn ($q) => $q->whereNull('status_batal')->orWhere('status_batal', 0))
            ->get(['barang_id', 'supplier_id']);

        $supplierDistributor = DB::table('supplier_distributor')
            ->where(fn ($q) => $q->whereNull('status_batal')->orWhere('status_batal', 0))
            ->get(['supplier_id', 'distributor_id']);

        $barangSupplierMap = [];
        foreach ($barangSupplier as $row) {
            $barangSupplierMap[$row->barang_id][] = $row->supplier_id;
        }

        $supplierDistributorMap = [];
        foreach ($supplierDistributor as $row) {
            $supplierDistributorMap[$row->supplier_id][] = $row->distributor_id;
        }

        return [$barangSupplierMap, $supplierDistributorMap];
    }

    private function gudangOptions()
    {
        $gudangs = Bagian::aktif()->where('referensi_bagian_id', 4)->orderBy('nama_bagian')->get();

        return $gudangs->isNotEmpty() ? $gudangs : Bagian::aktif()->orderBy('nama_bagian')->get();
    }

    private function validated(Request $request)
    {
        return array_merge([
            'tanggal_pemesanan' => now()->toDateTimeString(),
            'keterangan' => null,
        ], $request->validate([
            'bagian_id' => 'nullable|integer|exists:bagian,bagian_id',
            'tanggal_pemesanan' => 'nullable|date',
            'keterangan' => 'nullable|string|max:255',
        ]));
    }

    private function validatedItems(Request $request): array
    {
        $barangIds = (array) $request->input('barang_id', []);
        $supplierIds = (array) $request->input('supplier_id', []);
        $distributorIds = (array) $request->input('distributor_id', []);
        $jumlahs = (array) $request->input('jumlah_pesan', []);
        $hargas = (array) $request->input('harga_beli', []);
        $hargaJuals = (array) $request->input('harga_jual', []);

        $items = [];
        foreach ($barangIds as $i => $barangId) {
            if ($barangId === null || $barangId === '') {
                continue;
            }

            $jumlah = (float) ($jumlahs[$i] ?? 0);
            if ($jumlah <= 0) {
                continue;
            }

            $supplierId = ($supplierIds[$i] ?? null);
            if ($supplierId === null || $supplierId === '') {
                continue;
            }

            $hargaJual = ($hargaJuals[$i] ?? null);
            $items[] = [
                'barang_id' => (int) $barangId,
                'supplier_id' => (int) $supplierId,
                'distributor_id' => ($distributorIds[$i] ?? null) !== '' && ($distributorIds[$i] ?? null) !== null ? (int) $distributorIds[$i] : null,
                'jumlah_pesan' => $jumlah,
                'harga_beli' => ($hargas[$i] ?? null) !== '' && ($hargas[$i] ?? null) !== null ? (float) $hargas[$i] : null,
                'harga_jual' => $hargaJual !== '' && $hargaJual !== null ? (float) $hargaJual : null,
            ];
        }

        return $items;
    }
}
