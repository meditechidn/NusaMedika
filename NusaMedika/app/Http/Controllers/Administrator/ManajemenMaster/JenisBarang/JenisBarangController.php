<?php

namespace App\Http\Controllers\Administrator\ManajemenMaster\JenisBarang;

use App\Http\Controllers\Controller;
use App\Models\BarangJenis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JenisBarangController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $query = BarangJenis::aktif();

        if ($search !== '') {
            $query->where('nama_jenis_barang', 'ilike', "%{$search}%");
        }

        $jenisBarangList = $query->orderBy('barang_jenis_id')->paginate(10)->withQueryString();

        return view('moduls.Administrator.ManajemenMaster.JenisBarang.jenis_barang', compact('jenisBarangList', 'search'));
    }

    public function create()
    {
        return view('moduls.Administrator.ManajemenMaster.JenisBarang.jenis_barang_create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::beginTransaction();
        try {
            $jenisBarang = new BarangJenis;
            $jenisBarang->nama_jenis_barang = $data['nama_jenis_barang'];
            $jenisBarang->input_time = now();
            $jenisBarang->input_user_id = Auth::id();
            $jenisBarang->status_batal = 0;
            $jenisBarang->save();

            DB::commit();

            return redirect()->route('admin.jenis_barang.index')->with('success', 'Jenis barang berhasil ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyimpan jenis barang: '.$e->getMessage())->withInput();
        }
    }

    public function edit($jenisBarang)
    {
        $jenisBarang = BarangJenis::findOrFail($jenisBarang);

        return view('moduls.Administrator.ManajemenMaster.JenisBarang.jenis_barang_edit', compact('jenisBarang'));
    }

    public function update(Request $request, $jenisBarang)
    {
        $jenisBarang = BarangJenis::findOrFail($jenisBarang);
        $data = $this->validated($request, (int) $jenisBarang->barang_jenis_id);

        DB::beginTransaction();
        try {
            $jenisBarang->nama_jenis_barang = $data['nama_jenis_barang'];
            $jenisBarang->mod_time = now();
            $jenisBarang->mod_user_id = Auth::id();
            $jenisBarang->save();

            DB::commit();

            return redirect()->route('admin.jenis_barang.index')->with('success', 'Jenis barang berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memperbarui jenis barang: '.$e->getMessage())->withInput();
        }
    }

    public function destroy($jenisBarang)
    {
        DB::beginTransaction();
        try {
            $jenisBarang = BarangJenis::findOrFail($jenisBarang);
            $jenisBarang->status_batal = 1;
            $jenisBarang->mod_time = now();
            $jenisBarang->mod_user_id = Auth::id();
            $jenisBarang->save();

            DB::commit();

            return redirect()->route('admin.jenis_barang.index')->with('success', 'Jenis barang berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus jenis barang: '.$e->getMessage());
        }
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'nama_jenis_barang' => 'required|string|max:255',
        ]);

        $nama = trim($data['nama_jenis_barang']);

        $duplikat = BarangJenis::aktif()
            ->where('nama_jenis_barang', 'ilike', $nama)
            ->when($ignoreId !== null, fn ($q) => $q->where('barang_jenis_id', '!=', $ignoreId))
            ->exists();

        if ($duplikat) {
            throw ValidationException::withMessages([
                'nama_jenis_barang' => 'Nama jenis barang "'.$nama.'" sudah ada. Gunakan data yang sudah terdaftar.',
            ]);
        }

        $data['nama_jenis_barang'] = $nama;

        return $data;
    }
}
