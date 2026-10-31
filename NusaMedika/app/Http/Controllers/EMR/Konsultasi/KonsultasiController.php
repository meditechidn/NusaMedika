<?php

namespace App\Http\Controllers\EMR\Konsultasi;

use App\Helpers\AksesEhr;
use App\Helpers\BillingHelper;
use App\Helpers\EmrHelper;
use App\Helpers\GenerateHelper;
use App\Http\Controllers\Controller;
use App\Models\Bagian;
use App\Models\JadwalDokter;
use App\Models\Pegawai;
use App\Models\RegistrasiDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Form EMR "Formulir → Konsultasi" (multi-guna):
 *  - Konsultasi Rehabilitasi Medik → terbitkan registrasi_detail bagian rehab.
 *  - Konsul Layanan Klinik         → terbitkan registrasi_detail poli tujuan + registrasi_urut
 *                                    + penanggung_rawat (muncul di List Pasien Dokter).
 *  - Rencana Kontrol Rawat Jalan   → hanya simpan EMR (bisa dicetak).
 */
class KonsultasiController extends Controller
{
    private const FORM_SLUG = 'konsultasi';

    public function index($registrasi_detail_id, $emr_id = null)
    {
        $registrasi_detail = RegistrasiDetail::with('registrasi.pasien')->findOrFail($registrasi_detail_id);

        $form_id = EmrHelper::formIdBySlug(self::FORM_SLUG);
        abort_unless($form_id, 404);
        abort_unless(AksesEhr::can((int) $form_id, 'read'), 403);

        $aksesCrud = AksesEhr::flags((int) $form_id);

        $riwayat_konsultasi = EmrHelper::emrList((int) $form_id, (int) $registrasi_detail->registrasi_id);

        if (! $emr_id && ! ($aksesCrud['create'] ?? false) && $riwayat_konsultasi->isNotEmpty()) {
            return redirect()->route('emr.dynamic.index', [
                'form_name' => self::FORM_SLUG,
                'registrasi_detail_id' => $registrasi_detail_id,
                'emr_id' => $riwayat_konsultasi->first()->emr_id,
                'action' => 'view',
            ]);
        }

        $edit_konsultasi = null;
        $formData = [];
        if ($emr_id) {
            $edit_konsultasi = EmrHelper::emrById((int) $emr_id);

            if ($edit_konsultasi) {
                $details = EmrHelper::emrDetailByVariabel((int) $edit_konsultasi->emr_id);

                $formData = [
                    'jenis_konsultasi' => $details['jenis_konsultasi'] ?? '',
                    'bagian_rehab_id' => $details['bagian_rehab_id'] ?? '',
                    'bagian_tujuan_id' => $details['bagian_tujuan_id'] ?? '',
                    'dokter_tujuan_id' => $details['dokter_tujuan_id'] ?? '',
                    'tanggal_kontrol' => $details['tanggal_kontrol'] ?? '',
                    'indikasi_konsultasi' => $details['indikasi_konsultasi'] ?? '',
                    'catatan' => $details['catatan'] ?? '',
                ];

                if (empty($details)) {
                    $jsonData = json_decode($edit_konsultasi->data, true) ?? [];
                    foreach ($formData as $key => $value) {
                        $formData[$key] = $jsonData[$key] ?? '';
                    }
                }
            }
        }

        // Bagian penunjang Rehabilitasi Medik (referensi 6 + LIKE '%REHABILITASI%')
        $bagianRehabList = Bagian::aktif()
            ->where('referensi_bagian_id', 6)
            ->where('nama_bagian', 'ilike', '%REHABILITASI%')
            ->orderBy('nama_bagian')
            ->get();

        // Poliklinik (referensi 1) & dokter (profesi 1) tujuan
        $polikliniks = Bagian::aktif()
            ->where('referensi_bagian_id', 1)
            ->orderBy('nama_bagian')
            ->get();

        $dokters = Pegawai::aktif()
            ->where('profesi_id', 1)
            ->orderBy('nama_pegawai')
            ->get();

        // Map dokter -> poliklinik (via jadwal aktif) untuk filter dropdown dokter per poli
        $dokterPoliMap = [];
        foreach (JadwalDokter::aktif()->with('bagian')->get() as $jd) {
            if (! $jd->bagian || (int) $jd->bagian->referensi_bagian_id !== 1) {
                continue;
            }
            $dokterPoliMap[$jd->pegawai_id][] = (int) $jd->bagian_id;
        }

        // Riwayat kunjungan (dropdown History)
        $history_kunjungan = DB::table('registrasi_detail')
            ->join('registrasi', 'registrasi_detail.registrasi_id', '=', 'registrasi.registrasi_id')
            ->leftJoin('bagian', function ($join) {
                $join->on('registrasi_detail.bagian_id', '=', 'bagian.bagian_id')
                    ->where(function ($q) {
                        $q->whereNull('bagian.status_batal')->orWhere('bagian.status_batal', 0);
                    });
            })
            ->where('registrasi.pasien_id', $registrasi_detail->registrasi->pasien_id)
            ->where(function ($q) {
                $q->whereNull('registrasi.status_batal')->orWhere('registrasi.status_batal', 0);
            })
            ->where(function ($q) {
                $q->whereNull('registrasi_detail.status_batal')->orWhere('registrasi_detail.status_batal', 0);
            })
            ->select('registrasi.tgl_masuk', 'bagian.nama_bagian', 'registrasi_detail.registrasi_detail_id')
            ->orderBy('registrasi.tgl_masuk', 'desc')
            ->get();

        $history_grouped = [];
        foreach ($history_kunjungan as $hk) {
            $date = date('Y-m-d', strtotime($hk->tgl_masuk));
            if (! isset($history_grouped[$date])) {
                $history_grouped[$date] = [];
            }
            if ($hk->nama_bagian) {
                $history_grouped[$date][$hk->nama_bagian] = $hk->registrasi_detail_id;
            }
        }

        $isView = request('action') === 'view';

        return view('moduls.EMR.Konsultasi.index', compact(
            'registrasi_detail',
            'edit_konsultasi',
            'formData',
            'form_id',
            'history_grouped',
            'isView',
            'aksesCrud',
            'bagianRehabList',
            'polikliniks',
            'dokters',
            'dokterPoliMap'
        ));
    }

    public function store(Request $request, $registrasi_detail_id)
    {
        $form_id = EmrHelper::formIdBySlug(self::FORM_SLUG);
        abort_unless($form_id, 404);
        abort_unless(AksesEhr::can((int) $form_id, 'create'), 403);

        // Field dari blok yang tersembunyi (tidak dipilih) terkirim sebagai string kosong.
        // Normalisasi ke null supaya rule nullable/integer/date tidak menolak field yang
        // memang tidak dipakai (mis. bagian_tujuan_id saat jenis = REHABILITASI_MEDIK).
        $request->merge([
            'bagian_rehab_id' => $request->input('bagian_rehab_id') ?: null,
            'bagian_tujuan_id' => $request->input('bagian_tujuan_id') ?: null,
            'dokter_tujuan_id' => $request->input('dokter_tujuan_id') ?: null,
            'tanggal_kontrol' => $request->input('tanggal_kontrol') ?: null,
        ]);

        $request->validate([
            'jenis_konsultasi' => 'required|in:REHABILITASI_MEDIK,KONSUL_LAYANAN,RENCANA_KONTROL',
            'bagian_rehab_id' => 'required_if:jenis_konsultasi,REHABILITASI_MEDIK|nullable|integer|exists:bagian,bagian_id',
            'bagian_tujuan_id' => 'required_if:jenis_konsultasi,KONSUL_LAYANAN,RENCANA_KONTROL|nullable|integer|exists:bagian,bagian_id',
            'dokter_tujuan_id' => 'required_if:jenis_konsultasi,KONSUL_LAYANAN,RENCANA_KONTROL|nullable|integer|exists:pegawai,pegawai_id',
            'tanggal_kontrol' => 'required_if:jenis_konsultasi,RENCANA_KONTROL|nullable|date',
            'indikasi_konsultasi' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        $registrasi_detail = RegistrasiDetail::with('registrasi')->findOrFail($registrasi_detail_id);

        $user = Auth::user();
        if ($user == null || $user->pegawai_id == null || $user->user_id == null) {
            return redirect()->back()->with('error', 'Sesi Anda Telah Habis Silahkan Login Kembali!');
        }

        $jenisKonsultasi = $request->input('jenis_konsultasi');

        try {
            DB::beginTransaction();

            // 1. Simpan EMR (emr + emr_detail)
            EmrHelper::insert((int) $form_id, $this->konsultasiData($request), (int) $registrasi_detail_id);

            if ($jenisKonsultasi === 'REHABILITASI_MEDIK') {
                // 2. Terbitkan registrasi_detail tujuan bagian rehab (masih satu registrasi)
                $this->terbitkanDetailTujuan($registrasi_detail, (int) $request->input('bagian_rehab_id'));
            } elseif ($jenisKonsultasi === 'KONSUL_LAYANAN') {
                // 2-5. Terbitkan registrasi_detail poli tujuan + registrasi_urut + penanggung_rawat
                $this->terbitkanKonsulLayanan($registrasi_detail, (int) $request->input('bagian_tujuan_id'), (int) $request->input('dokter_tujuan_id'));
            }
            // RENCANA_KONTROL: hanya EMR, tanpa registrasi/registrasi_detail baru.
            if ($jenisKonsultasi !== 'RENCANA_KONTROL') {
                BillingHelper::syncKonsultasi($registrasi_detail->registrasi, $registrasi_detail, (int) $request->input('dokter_tujuan_id'));
            }

            DB::commit();

            return redirect()->route('emr.dynamic.index', ['form_name' => self::FORM_SLUG, 'registrasi_detail_id' => $registrasi_detail_id])
                ->with('success', 'Konsultasi berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Gagal menyimpan konsultasi: '.$e->getMessage());
        }
    }

    public function update(Request $request, $registrasi_detail_id, $emr_id)
    {
        $form_id = EmrHelper::formIdBySlug(self::FORM_SLUG);
        abort_unless($form_id, 404);
        abort_unless(AksesEhr::can((int) $form_id, 'update'), 403);

        // Normalisasi field blok tersembunyi (sama seperti store) agar rule nullable
        // tidak menolak nilai kosong dari blok yang tidak dipakai.
        $request->merge([
            'bagian_rehab_id' => $request->input('bagian_rehab_id') ?: null,
            'bagian_tujuan_id' => $request->input('bagian_tujuan_id') ?: null,
            'dokter_tujuan_id' => $request->input('dokter_tujuan_id') ?: null,
            'tanggal_kontrol' => $request->input('tanggal_kontrol') ?: null,
        ]);

        $request->validate([
            'jenis_konsultasi' => 'required|in:REHABILITASI_MEDIK,KONSUL_LAYANAN,RENCANA_KONTROL',
            'bagian_rehab_id' => 'required_if:jenis_konsultasi,REHABILITASI_MEDIK|nullable|integer|exists:bagian,bagian_id',
            'bagian_tujuan_id' => 'required_if:jenis_konsultasi,KONSUL_LAYANAN,RENCANA_KONTROL|nullable|integer|exists:bagian,bagian_id',
            'dokter_tujuan_id' => 'required_if:jenis_konsultasi,KONSUL_LAYANAN,RENCANA_KONTROL|nullable|integer|exists:pegawai,pegawai_id',
            'tanggal_kontrol' => 'required_if:jenis_konsultasi,RENCANA_KONTROL|nullable|date',
            'indikasi_konsultasi' => 'required|string',
            'catatan' => 'nullable|string',
        ]);

        try {
            // Update hanya menyentuh EMR — registrasi_detail tujuan yang sudah terbit tidak diubah.
            EmrHelper::update((int) $emr_id, (int) $form_id, $this->konsultasiData($request));

            return redirect()->route('emr.dynamic.index', ['form_name' => self::FORM_SLUG, 'registrasi_detail_id' => $registrasi_detail_id])
                ->with('success', 'Konsultasi berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui konsultasi: '.$e->getMessage());
        }
    }

    public function destroy($registrasi_detail_id, $emr_id)
    {
        $form_id = EmrHelper::formIdBySlug(self::FORM_SLUG);
        abort_unless($form_id, 404);
        abort_unless(AksesEhr::can((int) $form_id, 'delete'), 403);

        try {
            EmrHelper::delete((int) $emr_id);

            return redirect()->route('emr.dynamic.index', ['form_name' => self::FORM_SLUG, 'registrasi_detail_id' => $registrasi_detail_id])
                ->with('success', 'Konsultasi berhasil dibatalkan');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan konsultasi: '.$e->getMessage());
        }
    }

    /**
     * Cetak slip konsultasi — dibedakan per jenis:
     *  - RENCANA_KONTROL  → lembar "Rencana Kontrol Rawat Jalan" (untuk diserahkan ke pasien)
     *  - REHABILITASI_MEDIK / KONSUL_LAYANAN → slip rujukan konsultasi (bukti & instruksi ke bagian tujuan)
     */
    public function print($emr_id)
    {
        $form_id = EmrHelper::formIdBySlug(self::FORM_SLUG);
        abort_unless($form_id, 404);
        abort_unless(AksesEhr::can((int) $form_id, 'read'), 403);
        $emr = EmrHelper::emrById((int) $emr_id);
        abort_unless($emr, 404);

        return view('moduls.EMR.Konsultasi.print', compact('emr_id'));
    }

    /**
     * Petakan field form Konsultasi ke variabel yang tersimpan di emr_detail.
     */
    private function konsultasiData(Request $request): array
    {
        return [
            'jenis_konsultasi' => $request->input('jenis_konsultasi'),
            'bagian_rehab_id' => $request->input('bagian_rehab_id'),
            'bagian_tujuan_id' => $request->input('bagian_tujuan_id'),
            'dokter_tujuan_id' => $request->input('dokter_tujuan_id'),
            'tanggal_kontrol' => $request->input('tanggal_kontrol'),
            'indikasi_konsultasi' => $request->input('indikasi_konsultasi'),
            'catatan' => $request->input('catatan'),
        ];
    }

    /**
     * Terbitkan registrasi_detail tujuan dalam registrasi yang sama (konsul rehab / konsul layanan).
     * Snapshot kelas & hak kelas dari detail asal.
     */
    private function terbitkanDetailTujuan(RegistrasiDetail $asal, int $bagianTujuanId): int
    {
        return DB::table('registrasi_detail')->insertGetId([
            'registrasi_id' => $asal->registrasi_id,
            'bagian_id' => $bagianTujuanId,
            'kelas_id' => $asal->kelas_id,
            'hak_kelas_id' => $asal->hak_kelas_id,
            'bagian_asal_id' => $asal->bagian_id,
            'terima_dari' => 'INTERNAL',
            'tgl_daftar' => now(),
            'status_batal' => 0,
            'input_time' => now(),
            'input_user_id' => auth()->id(),
        ], 'registrasi_detail_id');
    }

    /**
     * Konsul Layanan Klinik (konsul internal poli):
     *  - registrasi_detail tujuan poli,
     *  - registrasi_urut (antrian dokter konsulen),
     *  - penanggung_rawat (rawat_user_id = user dokter konsulen).
     */
    private function terbitkanKonsulLayanan(RegistrasiDetail $asal, int $bagianTujuanId, int $dokterTujuanId): void
    {
        $registrasi = $asal->registrasi;

        $registrasiDetailId = DB::table('registrasi_detail')->insertGetId([
            'registrasi_id' => $asal->registrasi_id,
            'bagian_id' => $bagianTujuanId,
            'kelas_id' => $asal->kelas_id,
            'hak_kelas_id' => $asal->hak_kelas_id,
            'bagian_asal_id' => $asal->bagian_id,
            'terima_dari' => 'INTERNAL',
            'tgl_daftar' => now(),
            'status_batal' => 0,
            'input_time' => now(),
            'input_user_id' => auth()->id(),
        ], 'registrasi_detail_id');

        // registrasi_urut — antrian dokter konsulen (urutan dihitung dari jadwal hari ini)
        $tglKunjungan = now()->toDateString();
        $jadwal = JadwalDokter::aktif()
            ->where('pegawai_id', $dokterTujuanId)
            ->where('bagian_id', $bagianTujuanId)
            ->orderBy('hari')
            ->first();

        $urutan = GenerateHelper::generateNoUrut($dokterTujuanId, $bagianTujuanId, $tglKunjungan);
        $estimasi = $jadwal
            ? GenerateHelper::hitungEstimasi($tglKunjungan, $jadwal->waktu_mulai, $urutan)
            : now();

        DB::table('registrasi_urut')->insert([
            'registrasi_detail_id' => $registrasiDetailId,
            'pegawai_id' => $dokterTujuanId,
            'bagian_id' => $bagianTujuanId,
            'urutan' => $urutan,
            'tgl_urut' => $estimasi,
            'status_batal' => 0,
            'input_time' => now(),
            'input_user_id' => auth()->id(),
        ]);

        // penanggung_rawat — rawat_user_id = user_id dokter konsulen
        $rawatUserId = User::where('pegawai_id', $dokterTujuanId)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->value('user_id');

        DB::table('penanggung_rawat')->insert([
            'registrasi_id' => $asal->registrasi_id,
            'rawat_user_id' => $rawatUserId ?: auth()->id(),
            'status_batal' => 0,
            'input_time' => now(),
            'input_user_id' => auth()->id(),
        ]);
    }
}
