<?php

namespace App\Http\Controllers\EMR\TindakanMedis;

use App\Helpers\AksesEhr;
use App\Helpers\BillingHelper;
use App\Helpers\EmrHelper;
use App\Helpers\StockHelper;
use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\BarangJenis;
use App\Models\Pegawai;
use App\Models\RegistrasiDetail;
use App\Models\Tindakan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Form Tindakan Medis (slug `tindakan_medis`), sub-menu dashboard "Catatan Medis".
 *
 * Satu baris pencatatan: atas persetujuan dokter, tindakan (Master Tindakan),
 * jenis permintaan (CITO/BIASA), tanggal & jam tindakan, hasil/kondisi pasca
 * tindakan, keterangan, serta pemakaian obat/BMHP.
 *
 * ## Baris obat/BMHP
 * Setiap baris dimulai dari pilihan **jenis barang** (Obat/BMHP), lalu memilih
 * barang, lalu **nomor batch** beserta sisa stok yang tersedia. Nomor batch dan
 * sisa stok diambil dari tabel `stock` pada bagian tempat pasien dirawat
 * (`registrasi_detail.bagian_id`), lalu saat form disimpan stok batch tersebut
 * dikurangi lewat `StockHelper::catatPemakaian()` (kartu_stock jenis
 * "Pemakaian").
 *
 * Baris disimpan di emr_detail dengan variabel BERSUFFIX per baris
 * (jenis_barang_1, obat_1, batch_1, jumlah_1, ...). Suffiksanya penting: kalau
 * N baris memakai variabel yang sama, `EmrHelper::emrDetailByVariabel()` yang
 * `pluck('value','variabel')` akan menimpa duplikat dan item kedua dst. hilang
 * saat form dibuka lagi.
 *
 * Yang disimpan tetap **ID saja** — nama barang dan nomor batch tidak disimpan
 * di kolom terpisah, melainkan di-resolve dari master saat tampilan.
 */
class TindakanMedisController extends Controller
{
    private const SLUG = 'tindakan_medis';

    /** Maksimum baris obat/BMHP per pencatatan (cocok dengan mapping seeder). */
    private const MAKS_BARIS_OBAT = 20;

    public function index($registrasi_detail_id, $emr_id = null)
    {
        $registrasi_detail = RegistrasiDetail::with('registrasi.pasien')->findOrFail($registrasi_detail_id);

        $form_id = EmrHelper::formIdBySlug(self::SLUG);
        abort_unless($form_id, 404);
        abort_unless(AksesEhr::can((int) $form_id, 'read'), 403);

        $aksesCrud = AksesEhr::flags((int) $form_id);

        $riwayat = EmrHelper::emrList((int) $form_id, (int) $registrasi_detail->registrasi_id);

        // Tidak boleh membuat data baru -> tampilkan riwayat terakhir.
        if (empty($emr_id) && ! ($aksesCrud['create'] ?? false) && $riwayat->isNotEmpty()) {
            return redirect()->route('emr.dynamic.index', [
                'form_name' => self::SLUG,
                'registrasi_detail_id' => $registrasi_detail_id,
                'emr_id' => $riwayat->first()->emr_id,
                'action' => 'view',
            ]);
        }

        // Dropdown: dokter (profesi 1) & tindakan aktif dari Master Tindakan.
        $dokters = Pegawai::where('profesi_id', 1)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderBy('nama_pegawai')
            ->get();

        $tindakans = Tindakan::aktif()->orderBy('nama_tindakan')->get();

        // Master barang + jenis barang untuk cascade Jenis -> Barang -> Batch.
        $jenisBarangs = BarangJenis::aktif()->orderBy('barang_jenis_id')->get();

        $barangMap = [];
        foreach (Barang::aktif()->with('satuan')->orderBy('nama_barang')->get() as $barang) {
            $barangMap[$barang->barang_id] = [
                'nama' => $barang->nama_barang,
                'kode' => $barang->kode_barang,
                'satuan' => $barang->satuan?->singkatan,
                'jenis_barang_id' => (int) $barang->jenis_barang_id,
            ];
        }

        // Sisa stok per batch pada bagian tempat pasien dirawat.
        $bagianId = (int) $registrasi_detail->bagian_id;
        $stokMap = StockHelper::sisaPerBatch($bagianId);

        // Riwayat KUNJUNGAN untuk dropdown "History" (bukan riwayat emr; itu
        // ditangani komponen x-emr-history-table).
        $historyGrouped = EmrHelper::historyKunjunganGrouped($registrasi_detail);

        if (empty($emr_id)) {
            $emr_data = EmrHelper::wrapData([
                'dokter_persetujuan_id' => '',
                'tindakan_id' => '',
                'jenis_permintaan' => 'BIASA',
                'tanggal_tindakan' => '',
                'waktu_tindakan' => '',
                'hasil_kondisi' => '',
                'keterangan' => '',
                'pemakaian_obat_bmhp' => 'Tidak',
            ]);

            $formAction = route('emr.form.store', ['form_name' => self::SLUG, 'registrasi_detail_id' => $registrasi_detail_id]);
            $isEdit = false;
            $deleteAction = '';
            $isView = false;
        } else {
            $emr_data = EmrHelper::emrDetailByVariabel((int) $emr_id);

            $formAction = route('emr.form.update', ['form_name' => self::SLUG, 'registrasi_detail_id' => $registrasi_detail_id, 'emr_id' => $emr_id]);
            $isEdit = true;
            $deleteAction = route('emr.form.destroy', ['form_name' => self::SLUG, 'registrasi_detail_id' => $registrasi_detail_id, 'emr_id' => $emr_id]);
            $isView = request('action') === 'view';
        }

        // Baris obat/BMHP tersimpan, lengkapi dengan nama barang & sisa stok
        // batch terpilih supaya form edit langsung menampilkan apa adanya.
        $barisObat = $this->barisObatDariEmrData($emr_data, $barangMap, $stokMap);

        return view('moduls.EMR.TindakanMedis.index', compact(
            'registrasi_detail',
            'riwayat',
            'dokters',
            'tindakans',
            'jenisBarangs',
            'barangMap',
            'stokMap',
            'barisObat',
            'emr_data',
            'historyGrouped',
            'formAction',
            'isEdit',
            'deleteAction',
            'isView',
            'emr_id',
            'aksesCrud'
        ));
    }

    public function store(Request $request, $registrasi_detail_id)
    {
        $form_id = EmrHelper::formIdBySlug(self::SLUG);
        abort_unless($form_id, 404);
        abort_unless(AksesEhr::can((int) $form_id, 'create'), 403);

        $registrasi_detail = RegistrasiDetail::findOrFail($registrasi_detail_id);

        $user = Auth::user();
        if (! $user || ! $user->pegawai_id || ! $user->user_id) {
            return redirect()->back()->with('error', 'Sesi Anda Telah Habis Silahkan Login Kembali!');
        }

        try {
            $bagianId = (int) $registrasi_detail->bagian_id;
            $v = $this->validated($request, $bagianId);

            DB::transaction(function () use ($request, $form_id, $registrasi_detail_id, $bagianId, $v) {
                $emrId = EmrHelper::insert((int) $form_id, $this->filteredData($request, (int) $form_id, $v['data']), (int) $registrasi_detail_id);

                $this->catatPemakaian($v['baris'], $bagianId, $emrId);
                BillingHelper::syncTindakanMedis($emrId);
            });

            return redirect()->back()->with('success', 'Tindakan Medis berhasil disimpan.');
        } catch (ValidationException $e) {
            // Biarkan Laravel yang merender pesan validasi ke sesi.
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan tindakan medis: '.$e->getMessage())->withInput();
        }
    }

    public function update(Request $request, $registrasi_detail_id, $emr_id)
    {
        $form_id = EmrHelper::formIdBySlug(self::SLUG);
        abort_unless($form_id, 404);
        abort_unless(AksesEhr::can((int) $form_id, 'update'), 403);

        try {
            $bagianId = (int) RegistrasiDetail::findOrFail($registrasi_detail_id)->bagian_id;

            // Baris lama dibaca lebih dulu: stok yang sebentar lagi dikembalikan
            // harus ikut dihitung sebagai "tersedia" saat validasi, kalau tidak
            // editsisipan yang sah ikut ditolak.
            $barisLama = $this->barisObatTersimpan((int) $emr_id);
            $v = $this->validated($request, $bagianId, $this->petakanKembalian($barisLama));

            DB::transaction(function () use ($request, $form_id, $emr_id, $bagianId, $v, $barisLama) {
                // Kembalikan stok pemakaian lama SEBELUM detail lama dihapus,
                // supaya jumlah lama tidak hilang dari kartu_stock.
                $this->kembalikanPemakaian($barisLama, $bagianId);

                EmrHelper::update((int) $emr_id, (int) $form_id, $this->filteredData($request, (int) $form_id, $v['data']));

                $this->catatPemakaian($v['baris'], $bagianId, (int) $emr_id);
                BillingHelper::voidEmr((int) $emr_id);
                BillingHelper::syncTindakanMedis((int) $emr_id);
            });

            return redirect()->back()->with('success', 'Tindakan Medis berhasil diperbarui.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui tindakan medis: '.$e->getMessage())->withInput();
        }
    }

    public function destroy($registrasi_detail_id, $emr_id)
    {
        $form_id = EmrHelper::formIdBySlug(self::SLUG);
        abort_unless($form_id, 404);
        abort_unless(AksesEhr::can((int) $form_id, 'delete'), 403);

        try {
            $bagianId = (int) RegistrasiDetail::findOrFail($registrasi_detail_id)->bagian_id;

            DB::transaction(function () use ($emr_id, $bagianId) {
                $this->kembalikanPemakaian($this->barisObatTersimpan((int) $emr_id), $bagianId);

                EmrHelper::delete((int) $emr_id);
                BillingHelper::voidEmr((int) $emr_id);
            });

            return redirect()->back()->with('success', 'Tindakan Medis berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus tindakan medis: '.$e->getMessage());
        }
    }

    /**
     * Susun payload simpan.
     *
     * Baris obat/BMHP diambil berdasarkan SUFFIX nama field, bukan urutan array,
     * sehingga menghapus baris di tengah tidak menggeser pasangan
     * obat_N dengan jumlah_N.
     */
    private function filteredData(Request $request, int $formId, array $validated = []): array
    {
        $mapped = array_flip(EmrHelper::objekVariabels($formId));
        $data = array_intersect_key($request->all(), $mapped);

        // Field utama pakai nilai yang sudah divalidasi server, bukan nilai
        // mentah dari browser.
        foreach ($validated as $key => $value) {
            $data[$key] = $value;
        }

        // Baris obat/BMHP hanya bermakna bila pemakaian obat/BMHP = Ya. Saat
        // "Tidak" seluruh baris dibuang -- termasuk bila browser tetap mengirim
        // nya -- supaya tidak ada baris yatim di emr_detail yang akan muncul
        // lagi saat form dibuka.
        $pakaiObat = ($validated['pemakaian_obat_bmhp'] ?? 'Tidak') === 'Ya';

        for ($i = 1; $i <= self::MAKS_BARIS_OBAT; $i++) {
            if (! $pakaiObat || trim((string) ($data['obat_'.$i] ?? '')) === '') {
                foreach (['jenis_barang_', 'obat_', 'batch_', 'jumlah_'] as $prefix) {
                    unset($data[$prefix.$i]);
                }
            }
        }

        return $data;
    }

    /**
     * Kumpulkan baris obat/BMHP yang terisi dari request, berpasangan per suffix.
     *
     * @return array<int, array{jenis_barang_id:int, barang_id:int, no_batch:string, jumlah:float}>
     */
    private function kumpulkanBarisObat(Request $request): array
    {
        $baris = [];

        for ($i = 1; $i <= self::MAKS_BARIS_OBAT; $i++) {
            $barangId = trim((string) $request->input('obat_'.$i, ''));

            if ($barangId === '') {
                continue;
            }

            $baris[$i] = [
                'jenis_barang_id' => (int) $request->input('jenis_barang_'.$i, 0),
                'barang_id' => (int) $barangId,
                'no_batch' => trim((string) $request->input('batch_'.$i, '')),
                'jumlah' => (float) $request->input('jumlah_'.$i, 0),
            ];
        }

        return $baris;
    }

    /**
     * Baris obat/BMHP yang sudah tersimpan pada satu emr, dibaca dari emr_detail.
     *
     * Dipakai untuk mengembalikan stok saat data diubah atau dihapus.
     *
     * @return array<int, array{barang_id:int, no_batch:string, jumlah:float}>
     */
    private function barisObatTersimpan(int $emrId): array
    {
        $details = EmrHelper::emrDetailByVariabel($emrId);

        $baris = [];
        for ($i = 1; $i <= self::MAKS_BARIS_OBAT; $i++) {
            $barangId = (int) ($details['obat_'.$i] ?? 0);

            if ($barangId <= 0) {
                continue;
            }

            $baris[$i] = [
                'barang_id' => $barangId,
                'no_batch' => (string) ($details['batch_'.$i] ?? ''),
                'jumlah' => (float) ($details['jumlah_'.$i] ?? 0),
            ];
        }

        return $baris;
    }

    /**
     * Baca baris obat/BMHP dari emr_data untuk prefill form edit, dilengkapi
     * nama barang dan sisa stok batch terpilih.
     */
    private function barisObatDariEmrData($emr_data, array $barangMap, array $stokMap): array
    {
        $baris = [];

        for ($i = 1; $i <= self::MAKS_BARIS_OBAT; $i++) {
            $barangId = (int) ($emr_data['obat_'.$i] ?? 0);

            if ($barangId <= 0) {
                continue;
            }

            $noBatch = (string) ($emr_data['batch_'.$i] ?? '');
            $meta = $barangMap[$barangId] ?? null;

            $baris[$i] = [
                'barang_id' => (string) $barangId,
                'no_batch' => $noBatch,
                'jumlah' => (string) ($emr_data['jumlah_'.$i] ?? ''),
                'jenis_barang_id' => (int) ($emr_data['jenis_barang_'.$i] ?? ($meta['jenis_barang_id'] ?? 0)),
                'nama' => $meta['nama'] ?? '(barang tidak aktif)',
                'satuan' => $meta['satuan'] ?? null,
                'stok' => (float) ($stokMap[$barangId][$noBatch]['jumlah'] ?? 0.0),
                'tgl_expired' => $stokMap[$barangId][$noBatch]['tgl_expired'] ?? null,
            ];
        }

        return $baris;
    }

    /**
     * Petakan stok yang akan dikembalikan dari baris lama, per
     * (barang_id + no_batch), supaya validasi edit tidak salah menghitung
     * stok yang tersedia.
     *
     * @return array<string, float>
     */
    private function petakanKembalian(array $barisLama): array
    {
        $kembalian = [];

        foreach ($barisLama as $item) {
            if ($item['jumlah'] <= 0 || $item['no_batch'] === '') {
                continue;
            }

            $key = $item['barang_id'].'|'.$item['no_batch'];
            $kembalian[$key] = ($kembalian[$key] ?? 0) + $item['jumlah'];
        }

        return $kembalian;
    }

    /** Catat stok keluar untuk setiap baris obat/BMHP yang dipakai. */
    private function catatPemakaian(array $baris, int $bagianId, ?int $emrId = null): void
    {
        foreach ($baris as $item) {
            StockHelper::catatPemakaian(
                $bagianId,
                $item['barang_id'],
                $item['no_batch'],
                $item['jumlah'],
                ['keterangan' => 'Pemakaian - Tindakan Medis', 'ref_emr_id' => $emrId]
            );
        }
    }

    /** Kembalikan stok untuk setiap baris obat/BMHP yang sebelumnya dipakai. */
    private function kembalikanPemakaian(array $baris, int $bagianId): void
    {
        foreach ($baris as $item) {
            if ($item['jumlah'] <= 0 || $item['no_batch'] === '') {
                continue;
            }

            StockHelper::kembalikanPemakaian(
                $bagianId,
                $item['barang_id'],
                $item['no_batch'],
                $item['jumlah'],
                ['keterangan' => 'Koreksi - Tindakan Medis']
            );
        }
    }

    /**
     * Validasi input form.
     *
     * Selain validasi dasar, server memeriksa ulang tiga syarat stok berikut
     * (nilai stok dari browser tidak pernah dipercaya):
     *  - jenis barang harus cocok dengan jenis barang master yang dipilih
     *  - nomor batch harus punya saldo > 0 pada bagian tempat pasien dirawat
     *  - jumlah tidak boleh melebihi sisa stok batch tersebut
     *
     * @return array{data: array, baris: array} data tervalidasi + baris obat siap catat stok
     */
    private function validated(Request $request, int $bagianId, array $kembalian = []): array
    {
        $data = array_merge([
            'hasil_kondisi' => null,
            'keterangan' => null,
        ], $request->validate([
            'dokter_persetujuan_id' => 'required|integer|exists:pegawai,pegawai_id',
            'tindakan_id' => 'required|integer|exists:tindakan,tindakan_id',
            'jenis_permintaan' => 'required|in:CITO,BIASA',
            'tanggal_tindakan' => 'required|date',
            'waktu_tindakan' => 'required|date_format:H:i',
            'hasil_kondisi' => 'nullable|string|max:1000',
            'keterangan' => 'nullable|string|max:1000',
            'pemakaian_obat_bmhp' => 'required|in:Ya,Tidak',
        ]));

        if ($data['pemakaian_obat_bmhp'] !== 'Ya') {
            return ['data' => $data, 'baris' => []];
        }

        $baris = $this->kumpulkanBarisObat($request);

        if ($baris === []) {
            throw ValidationException::withMessages([
                'pemakaian_obat_bmhp' => 'Pemakaian Obat/BMHP = Ya, minimal satu obat/BMHP wajib diisi.',
            ]);
        }

        // Sisa stok dibaca sekali per batch yang dipilih, dan JUMLAH yang
        // diminta dijumlahkan per (barang, batch).
        //
        // Penting: pemeriksaan TIDAK boleh per-baris. Kalau tiap baris dicek
        // sendiri terhadap sisa stok, N baris dengan batch sama bisa lolos
        // walau totalnya melebihi stok (stok jadi minus / negatif).
        $sisaCache = [];
        $terpakai = [];
        $sudahDipakai = [];
        $pemakaiBatch = [];

        foreach ($baris as $index => $item) {
            $barang = Barang::aktif()->with('jenis')->find($item['barang_id']);

            if (! $barang) {
                throw ValidationException::withMessages([
                    'obat_'.$index => 'Obat/BMHP tidak valid atau sudah nonaktif pada baris #'.$index.'.',
                ]);
            }

            if ($item['jenis_barang_id'] !== (int) $barang->jenis_barang_id) {
                throw ValidationException::withMessages([
                    'jenis_barang_'.$index => 'Jenis barang tidak sesuai dengan barang yang dipilih pada baris #'.$index.'.',
                ]);
            }

            if ($item['no_batch'] === '') {
                throw ValidationException::withMessages([
                    'batch_'.$index => 'Nomor batch wajib dipilih pada baris #'.$index.'.',
                ]);
            }

            if ($item['jumlah'] <= 0) {
                throw ValidationException::withMessages([
                    'jumlah_'.$index => 'Jumlah wajib lebih dari 0 pada baris #'.$index.'.',
                ]);
            }

            $cacheKey = $item['barang_id'].'|'.$item['no_batch'];

            // Satu barang + satu batch hanya boleh muncul SATU KALI per
            // pencatatan. Tanpa aturan ini, batch yang sama bisa diinput dua
            // baris dengan jumlah berbeda sehingga stok terpotong dobel.
            if (isset($sudahDipakai[$cacheKey])) {
                throw ValidationException::withMessages([
                    'batch_'.$index => 'Barang + batch '.$item['no_batch'].' sudah ditambahkan pada baris #'.$sudahDipakai[$cacheKey].'. Gabungkan jumlahnya menjadi satu baris.',
                ]);
            }

            $sudahDipakai[$cacheKey] = $index;

            // Nomor batch yang sama juga tidak boleh dipakai untuk barang lain:
            // nomor batch bersifat unik per fisik barang.
            if (isset($pemakaiBatch[$item['no_batch']]) && $pemakaiBatch[$item['no_batch']]['barang_id'] !== $item['barang_id']) {
                throw ValidationException::withMessages([
                    'batch_'.$index => 'Nomor batch '.$item['no_batch'].' sudah dipakai untuk barang lain pada baris #'.$pemakaiBatch[$item['no_batch']]['index'].'.',
                ]);
            }

            $pemakaiBatch[$item['no_batch']] = ['barang_id' => $item['barang_id'], 'index' => $index];

            $sisaCache[$cacheKey] ??= StockHelper::sisaBatch($bagianId, $item['barang_id'], $item['no_batch']);

            // Stok yang benar-benar tersedia. Saat mengubah data, stok lama yang
            // sebentar lagi dikembalikan ikut dihitung — kalau tidak, mengedit
            // baris yang memakai satu-satunya batch akan selalu ditolak karena
            // stoknya sedang 0.
            $tersedia = $sisaCache[$cacheKey] + ($kembalian[$cacheKey] ?? 0.0);

            if ($tersedia <= 0) {
                throw ValidationException::withMessages([
                    'batch_'.$index => 'Nomor batch tidak tersedia / stok habis pada baris #'.$index.'.',
                ]);
            }

            if (StockHelper::batchKedaluwarsa($bagianId, $item['barang_id'], $item['no_batch'])) {
                throw ValidationException::withMessages([
                    'batch_'.$index => 'Nomor batch pada baris #'.$index.' sudah kedaluwarsa dan tidak boleh dipakai.',
                ]);
            }

            // Akumulasi seluruh baris yang memakai batch sama, baru bandingkan
            // dengan stok tersedia.
            $terpakai[$cacheKey] = ($terpakai[$cacheKey] ?? 0) + $item['jumlah'];

            if ($terpakai[$cacheKey] > $tersedia) {
                $sisa = rtrim(rtrim(number_format($tersedia, 2, ',', '.'), '0'), ',');

                throw ValidationException::withMessages([
                    'jumlah_'.$index => 'Total jumlah untuk batch '.$item['no_batch'].' melebihi sisa stok (tersisa '.$sisa.').',
                ]);
            }
        }

        return ['data' => $data, 'baris' => $baris];
    }
}
