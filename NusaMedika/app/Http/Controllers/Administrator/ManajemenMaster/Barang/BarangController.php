<?php

namespace App\Http\Controllers\Administrator\ManajemenMaster\Barang;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\BarangJenis;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $query = Barang::aktif()->with(['jenis', 'satuan']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'ilike', "%{$search}%")
                    ->orWhere('kode_barang', 'ilike', "%{$search}%")
                    ->orWhereHas('jenis', fn ($jenis) => $jenis->where('nama_jenis_barang', 'ilike', "%{$search}%"));
            });
        }

        $barangList = $query->orderBy('nama_barang')->paginate(10)->withQueryString();

        return view('moduls.Administrator.ManajemenMaster.Barang.barang', compact('barangList', 'search'));
    }

    public function create()
    {
        $barangJenisList = $this->jenisOptions();
        $satuanList = $this->satuanOptions();

        return view('moduls.Administrator.ManajemenMaster.Barang.barang_create', compact('barangJenisList', 'satuanList'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::beginTransaction();
        try {
            $barang = new Barang;
            $barang->kode_barang = $data['kode_barang'];
            $barang->nama_barang = $data['nama_barang'];
            $barang->jenis_barang_id = $data['jenis_barang_id'];
            $barang->satuan_id = $data['satuan_id'];
            $barang->is_racikan = (int) $data['is_racikan'];
            $barang->is_fornas = (int) $data['is_fornas'];
            $barang->kategori_barang = (int) $data['kategori_barang'];
            $barang->input_time = now();
            $barang->input_user_id = Auth::id();
            $barang->status_batal = 0;
            $barang->save();

            DB::commit();

            return redirect()->route('admin.barang.index')->with('success', 'Barang berhasil ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyimpan barang: '.$e->getMessage())->withInput();
        }
    }

    public function edit($barang)
    {
        $barang = Barang::findOrFail($barang);

        $barangJenisList = $this->jenisOptions();
        $satuanList = $this->satuanOptions();

        return view('moduls.Administrator.ManajemenMaster.Barang.barang_edit', compact('barang', 'barangJenisList', 'satuanList'));
    }

    public function update(Request $request, $barang)
    {
        $data = $this->validated($request);

        DB::beginTransaction();
        try {
            $barang = Barang::findOrFail($barang);
            $barang->kode_barang = $data['kode_barang'];
            $barang->nama_barang = $data['nama_barang'];
            $barang->jenis_barang_id = $data['jenis_barang_id'];
            $barang->satuan_id = $data['satuan_id'];
            $barang->is_racikan = (int) $data['is_racikan'];
            $barang->is_fornas = (int) $data['is_fornas'];
            $barang->kategori_barang = (int) $data['kategori_barang'];
            $barang->mod_time = now();
            $barang->mod_user_id = Auth::id();
            $barang->save();

            DB::commit();

            return redirect()->route('admin.barang.index')->with('success', 'Barang berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memperbarui barang: '.$e->getMessage())->withInput();
        }
    }

    public function destroy($barang)
    {
        DB::beginTransaction();
        try {
            $barang = Barang::findOrFail($barang);
            $barang->status_batal = 1;
            $barang->mod_time = now();
            $barang->mod_user_id = Auth::id();
            $barang->save();

            DB::commit();

            return redirect()->route('admin.barang.index')->with('success', 'Barang berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus barang: '.$e->getMessage());
        }
    }

    private function jenisOptions()
    {
        return BarangJenis::aktif()->orderBy('nama_jenis_barang')->get();
    }

    private function satuanOptions()
    {
        return Satuan::aktif()->orderBy('nama_satuan')->get();
    }

    private function validated(Request $request)
    {
        $data = array_merge([
            'kode_barang' => null,
            'is_racikan' => 0,
            'is_fornas' => 0,
            'kategori_barang' => 0,
        ], $request->validate([
            'kode_barang' => 'nullable|string|max:50',
            'nama_barang' => 'required|string|max:255',
            'jenis_barang_id' => 'required|integer|exists:barang_jenis,barang_jenis_id',
            'satuan_id' => 'nullable|integer|exists:satuan,satuan_id',
            'is_racikan' => 'nullable|in:0,1',
            'is_fornas' => 'nullable|in:0,1,2',
            'kategori_barang' => 'nullable|in:0,1',
        ]));

        // Racikan hanya berlaku untuk jenis barang "Obat"; selain itu dipaksa 0
        // agar nilai yang tersembunyi di form tidak ikut tersimpan.
        $jenisObatId = (int) (BarangJenis::aktif()
            ->whereRaw('LOWER(BTRIM(nama_jenis_barang)) = ?', ['obat'])
            ->orderBy('barang_jenis_id')
            ->value('barang_jenis_id') ?? 0);

        if ($jenisObatId === 0 || (int) $data['jenis_barang_id'] !== $jenisObatId) {
            $data['is_racikan'] = 0;
        }

        return $data;
    }
}
