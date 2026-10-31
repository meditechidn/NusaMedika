<?php

namespace App\Http\Controllers\Registrasi\Pendaftaran\DaftarRanap;

use App\Helpers\BillingHelper;
use App\Http\Controllers\Controller;
use App\Models\Bagian;
use App\Models\DiagnosaRawat;
use App\Models\KelasRuang;
use App\Models\Nasabah;
use App\Models\PasienNasabah;
use App\Models\PenanggungRawat;
use App\Models\Registrasi;
use App\Models\RegistrasiDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DaftarRanapController extends Controller
{
    public function index()
    {
        $kelasList = KelasRuang::aktif()->orderBy('kelas_ruang_id')->get();

        $nasabahs = Nasabah::where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        })->orderBy('nama_nasabah')->get();

        return view('moduls.Registrasi.Pendaftaran.DaftarRanap.daftar_ranap', compact('kelasList', 'nasabahs'));
    }

    public function store(Request $request)
    {
        $data = array_merge([
            'kelas_id' => null,
            'dokter_id' => null,
            'icd_id' => null,
            'diagnosa_awal' => null,
            'nama_pj' => null,
            'hubungan_pj' => null,
            'nohp_pj' => null,
        ], $request->validate([
            'pasien_id' => 'required|integer|exists:pasien,pasien_id',
            'tgl_masuk' => 'required|date',
            'bagian_id' => 'required|integer|exists:bagian,bagian_id',
            'kelas_id' => 'nullable|integer|exists:kelas_ruang,kelas_ruang_id',
            'nasabah_id' => 'required|integer|exists:nasabah,nasabah_id',
            'asal_pasien_ranap' => 'required|string|max:50',
            'dokter_id' => 'nullable|integer|exists:pegawai,pegawai_id',
            'icd_id' => 'nullable|integer|exists:icd,icd_id',
            'diagnosa_awal' => 'nullable|string|max:1000',
            'nama_pj' => 'nullable|string|max:255',
            'hubungan_pj' => 'nullable|string|max:50',
            'nohp_pj' => 'nullable|string|max:20',
        ]));

        // Cek ruang yang dipilih benar milik referensi rawat inap
        $ruang = Bagian::where('bagian_id', $data['bagian_id'])
            ->where('referensi_bagian_id', env('REF_BAGIAN_RANAP', 2))
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->first();

        if (! $ruang) {
            return back()->withInput()->with('error', 'Ruang yang dipilih bukan ruang perawatan rawat inap.');
        }

        // Pasien tidak boleh punya rawat inap aktif
        $aktif = Registrasi::where('pasien_id', $data['pasien_id'])
            ->where('jenis_rawat', env('JENIS_RAWAT_RI', 'RI'))
            ->whereNull('tgl_keluar')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->exists();

        if ($aktif) {
            return back()->withInput()->with('error', 'Pasien masih memiliki rawat inap aktif.');
        }

        try {
            DB::beginTransaction();

            $pasienNasabah = PasienNasabah::where('pasien_id', $data['pasien_id'])
                ->where('nasabah_id', $data['nasabah_id'])
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->first();

            if (! $pasienNasabah) {
                $pasienNasabah = new PasienNasabah;
                $pasienNasabah->pasien_id = $data['pasien_id'];
                $pasienNasabah->nasabah_id = $data['nasabah_id'];
                $pasienNasabah->save();
            }

            $hakKelasId = $pasienNasabah->hak_kelas_id;

            // Kelas dipilih user; fallback ke hak kelas nasabah bila tidak dipilih.
            $kelasId = $data['kelas_id'] ?? $hakKelasId;
            $kelas = $kelasId
                ? KelasRuang::aktif()->where('kelas_ruang_id', $kelasId)->first()
                : null;

            $tglMasuk = date('Y-m-d H:i:s', strtotime($data['tgl_masuk']));

            $memo = json_encode([
                'indikasi' => $data['diagnosa_awal'] ?? null,
                'nama_pj' => $data['nama_pj'] ?? null,
                'hubungan_pj' => $data['hubungan_pj'] ?? null,
                'nohp_pj' => $data['nohp_pj'] ?? null,
            ], JSON_UNESCAPED_UNICODE);

            $registrasi = new Registrasi;
            $registrasi->pasien_id = $data['pasien_id'];
            $registrasi->tgl_masuk = $tglMasuk;
            $registrasi->jenis_rawat = env('JENIS_RAWAT_RI', 'RI');
            $registrasi->prioritas = $data['asal_pasien_ranap'];
            $registrasi->pasien_nasabah_id = $pasienNasabah->pasien_nasabah_id;
            $registrasi->memo = $memo;
            $registrasi->status_batal = 0;
            $registrasi->input_time = now();
            $registrasi->input_user_id = auth()->id();
            $registrasi->save();

            $registrasiDetail = new RegistrasiDetail;
            $registrasiDetail->registrasi_id = $registrasi->registrasi_id;
            $registrasiDetail->bagian_id = $data['bagian_id'];
            $registrasiDetail->kelas_id = $kelas?->kelas_bpjs;
            $registrasiDetail->hak_kelas_id = $kelas?->kelas_ruang_id;
            $registrasiDetail->terima_dari = 'DALAM';
            $registrasiDetail->tgl_daftar = now();
            $registrasiDetail->check_in = $tglMasuk;
            $registrasiDetail->status_batal = 0;
            $registrasiDetail->input_time = now();
            $registrasiDetail->input_user_id = auth()->id();
            $registrasiDetail->save();

            BillingHelper::syncAdministrasi($registrasi, $registrasiDetail);

            if (! empty($data['icd_id'])) {
                $diagnosaRawat = new DiagnosaRawat;
                $diagnosaRawat->registrasi_id = $registrasi->registrasi_id;
                $diagnosaRawat->icd_id = $data['icd_id'];
                $diagnosaRawat->jenis_diagnosa = 1;
                $diagnosaRawat->status_batal = 0;
                $diagnosaRawat->input_time = now();
                $diagnosaRawat->input_user_id = auth()->id();
                $diagnosaRawat->save();
            }

            $dokterId = $data['dokter_id'] ?? null;

            $rawatUserId = null;
            if ($dokterId) {
                $rawatUserId = User::where('pegawai_id', $dokterId)
                    ->where(function ($q) {
                        $q->whereNull('status_batal')->orWhere('status_batal', 0);
                    })
                    ->value('user_id');
            }

            $penanggungRawat = new PenanggungRawat;
            $penanggungRawat->registrasi_id = $registrasi->registrasi_id;
            $penanggungRawat->rawat_user_id = $rawatUserId ?? auth()->id();
            $penanggungRawat->status_batal = 0;
            $penanggungRawat->input_time = now();
            $penanggungRawat->input_user_id = auth()->id();
            $penanggungRawat->save();

            DB::commit();

            return redirect()->route('list_pasien_ranap.index')->with('success', 'Pendaftaran rawat inap berhasil. Pasien masuk daftar tunggu (waitlist) sampai bed ditempatkan di Bed Management.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
}
