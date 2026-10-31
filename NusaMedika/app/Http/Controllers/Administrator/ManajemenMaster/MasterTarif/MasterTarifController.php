<?php

namespace App\Http\Controllers\Administrator\ManajemenMaster\MasterTarif;

use App\Http\Controllers\Controller;
use App\Models\KategoriTindakan;
use App\Models\KelasRuang;
use App\Models\Tarif;
use App\Models\Tindakan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterTarifController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $kategoriId = (int) $request->input('kategori_tindakan_id');

        $query = Tindakan::aktif()
            ->with('kategori')
            ->with(['tarif' => fn ($q) => $q->orderByRaw('kelas_ruang_id IS NULL DESC')]);

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

        return view('moduls.Administrator.ManajemenMaster.MasterTarif.master_tarif', array_merge(
            compact('tindakanList', 'search', 'kategoriId'),
            $this->formData()
        ));
    }

    public function create(Request $request)
    {
        $tindakanId = (int) $request->input('tindakan_id');

        if ($tindakanId) {
            $tindakan = Tindakan::aktif()->with('kategori')->findOrFail($tindakanId);

            return redirect()
                ->route('admin.master_tarif.edit', ['master_tarif' => $tindakan->tindakan_id])
                ->with('info', 'Tindakan tersebut sudah ada. Silakan perbarui tarifnya.');
        }

        $belumAdaTarif = Tindakan::aktif()
            ->doesntHave('tarif')
            ->with('kategori')
            ->orderBy('nama_tindakan')
            ->get();

        return view('moduls.Administrator.ManajemenMaster.MasterTarif.master_tarif_create', array_merge(
            $this->formData(),
            compact('belumAdaTarif')
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::beginTransaction();
        try {
            $this->syncTarif((int) $data['tindakan_id'], (array) $request->input('tarif', []));

            DB::commit();

            return redirect()->route('admin.master_tarif.index')->with('success', 'Tarif berhasil disimpan.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menyimpan tarif: '.$e->getMessage())->withInput();
        }
    }

    public function edit($masterTarif)
    {
        $tindakan = Tindakan::aktif()->with('kategori')->findOrFail($masterTarif);

        $tarifByKelas = $tindakan->tarif
            ->mapWithKeys(fn ($h) => [(string) ($h->kelas_ruang_id ?? 'default') => $h->tarif])
            ->all();

        return view('moduls.Administrator.ManajemenMaster.MasterTarif.master_tarif_edit', array_merge(
            $this->formData(),
            compact('tindakan', 'tarifByKelas')
        ));
    }

    public function update(Request $request, $masterTarif)
    {
        $data = $this->validated($request);

        DB::beginTransaction();
        try {
            $tindakan = Tindakan::aktif()->findOrFail($masterTarif);

            $this->syncTarif((int) $tindakan->tindakan_id, (array) $request->input('tarif', []));

            DB::commit();

            return redirect()->route('admin.master_tarif.index')->with('success', 'Tarif berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal memperbarui tarif: '.$e->getMessage())->withInput();
        }
    }

    public function destroy($masterTarif)
    {
        DB::beginTransaction();
        try {
            $tindakan = Tindakan::aktif()->findOrFail($masterTarif);

            Tarif::where('tindakan_id', $tindakan->tindakan_id)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->update([
                    'status_batal' => 1,
                    'mod_time' => now(),
                    'mod_user_id' => Auth::id(),
                ]);

            DB::commit();

            return redirect()->route('admin.master_tarif.index')->with('success', 'Tarif berhasil dihapus.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Gagal menghapus tarif: '.$e->getMessage());
        }
    }

    /**
     * Soft-delete semua tarif aktif sebuah tindakan, lalu insert ulang dari form.
     * Baris "default" (kelas_ruang_id NULL) wajib; kelas yang kosong tidak di-insert
     * sehingga PenunjangHelper::tarif() jatuh ke tarif default.
     */
    private function syncTarif(int $tindakanId, array $tarifs): void
    {
        $user = Auth::id();
        $now = now();

        Tarif::where('tindakan_id', $tindakanId)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->update([
                'status_batal' => 1,
                'mod_time' => $now,
                'mod_user_id' => $user,
            ]);

        $kelasValid = KelasRuang::aktif()->pluck('kelas_ruang_id')->map(fn ($id) => (string) $id)->all();

        foreach ($tarifs as $key => $nilai) {
            if ($key !== 'default' && ! in_array((string) $key, $kelasValid, true)) {
                continue;
            }

            $tarif = is_numeric($nilai) ? (float) $nilai : null;

            // Baris default wajib ada walau kosong; kelas kosong dilewati.
            if ($key !== 'default' && $tarif === null) {
                continue;
            }

            $harga = new Tarif;
            $harga->tindakan_id = $tindakanId;
            $harga->kelas_ruang_id = $key === 'default' ? null : (int) $key;
            $harga->tarif = $tarif;
            $harga->input_time = $now;
            $harga->input_user_id = $user;
            $harga->status_batal = 0;
            $harga->save();
        }
    }

    private function validated(Request $request)
    {
        return $request->validate([
            'tindakan_id' => 'required|integer|exists:tindakan,tindakan_id',
            'tarif' => 'required|array',
            'tarif.default' => 'required|numeric|min:0',
            'tarif.*' => 'nullable|numeric|min:0',
        ]);
    }

    private function formData(): array
    {
        return [
            'kelasList' => KelasRuang::aktif()->orderBy('kelas_ruang_id')->get(),
            'kategoriList' => KategoriTindakan::aktif()->orderBy('nama_kategori_tindakan')->get(),
        ];
    }
}
