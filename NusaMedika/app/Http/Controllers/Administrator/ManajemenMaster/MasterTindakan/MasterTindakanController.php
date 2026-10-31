<?php

namespace App\Http\Controllers\Administrator\ManajemenMaster\MasterTindakan;

use App\Http\Controllers\Controller;
use App\Models\KategoriTindakan;
use App\Models\Tarif;
use App\Models\Tindakan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MasterTindakanController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $kategoriId = (int) $request->input('kategori_tindakan_id');

        $query = Tindakan::aktif()->with('kategori');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('kode_tindakan', 'ilike', "%{$search}%")
                    ->orWhere('nama_tindakan', 'ilike', "%{$search}%");
            });
        }

        if ($kategoriId) {
            $query->where('kategori_tindakan_id', $kategoriId);
        }

        $tindakanList = $query->orderBy('kode_tindakan')->paginate(10)->withQueryString();

        $kategoriList = KategoriTindakan::aktif()->orderBy('nama_kategori_tindakan')->get();

        return view('moduls.Administrator.ManajemenMaster.MasterTindakan.master_tindakan', compact(
            'tindakanList',
            'search',
            'kategoriId',
            'kategoriList'
        ));
    }

    public function create()
    {
        return view('moduls.Administrator.ManajemenMaster.MasterTindakan.master_tindakan_create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::beginTransaction();
        try {
            $tindakan = new Tindakan;
            $tindakan->kode_tindakan = strtoupper(trim($data['kode_tindakan']));
            $tindakan->nama_tindakan = $data['nama_tindakan'];
            $tindakan->kategori_tindakan_id = (int) $data['kategori_tindakan_id'];
            $tindakan->input_time = now();
            $tindakan->input_user_id = Auth::id();
            $tindakan->status_batal = 0;
            $tindakan->save();

            DB::commit();

            return redirect()->route('admin.master_tindakan.index')
                ->with('success', 'Tindakan berhasil ditambahkan. Tarifnya dapat diatur di Master Tarif.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyimpan tindakan: '.$e->getMessage())->withInput();
        }
    }

    public function edit($masterTindakan)
    {
        $tindakan = Tindakan::aktif()->findOrFail($masterTindakan);

        return view('moduls.Administrator.ManajemenMaster.MasterTindakan.master_tindakan_edit', array_merge(
            $this->formData(),
            compact('tindakan')
        ));
    }

    public function update(Request $request, $masterTindakan)
    {
        $data = $this->validated($request, $masterTindakan);

        DB::beginTransaction();
        try {
            $tindakan = Tindakan::aktif()->findOrFail($masterTindakan);
            $tindakan->kode_tindakan = strtoupper(trim($data['kode_tindakan']));
            $tindakan->nama_tindakan = $data['nama_tindakan'];
            $tindakan->kategori_tindakan_id = (int) $data['kategori_tindakan_id'];
            $tindakan->mod_time = now();
            $tindakan->mod_user_id = Auth::id();
            $tindakan->save();

            DB::commit();

            return redirect()->route('admin.master_tindakan.index')->with('success', 'Tindakan berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memperbarui tindakan: '.$e->getMessage())->withInput();
        }
    }

    public function destroy($masterTindakan)
    {
        DB::beginTransaction();
        try {
            $tindakan = Tindakan::aktif()->findOrFail($masterTindakan);
            $user = Auth::id();
            $now = now();

            Tindakan::where('tindakan_id', $tindakan->tindakan_id)
                ->update([
                    'status_batal' => 1,
                    'mod_time' => $now,
                    'mod_user_id' => $user,
                ]);

            Tarif::where('tindakan_id', $tindakan->tindakan_id)
                ->update([
                    'status_batal' => 1,
                    'mod_time' => $now,
                    'mod_user_id' => $user,
                ]);

            DB::table('group_tindakan_tindakan')
                ->where('tindakan_id', $tindakan->tindakan_id)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->update([
                    'status_batal' => 1,
                    'mod_time' => $now,
                    'mod_user_id' => $user,
                ]);

            DB::commit();

            return redirect()->route('admin.master_tindakan.index')->with('success', 'Tindakan berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus tindakan: '.$e->getMessage());
        }
    }

    private function validated(Request $request, $masterTindakan = null)
    {
        $ignore = $masterTindakan ? (int) $masterTindakan : null;

        return $request->validate([
            'kode_tindakan' => [
                'required',
                'string',
                'max:20',
                Rule::unique('tindakan', 'kode_tindakan')->ignore($ignore, 'tindakan_id'),
            ],
            'nama_tindakan' => 'required|string|max:255',
            'kategori_tindakan_id' => 'required|integer|exists:kategori_tindakan,kategori_tindakan_id',
        ]);
    }

    private function formData(): array
    {
        return [
            'kategoriList' => KategoriTindakan::aktif()->orderBy('nama_kategori_tindakan')->get(),
        ];
    }
}
