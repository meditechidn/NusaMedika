<?php

namespace App\Http\Controllers\Registrasi\Pendaftaran\DaftarGawatDarurat;

use App\Helpers\BillingHelper;
use App\Helpers\GenerateHelper;
use App\Http\Controllers\Controller;
use App\Models\Bagian;
use App\Models\DiagnosaRawat;
use App\Models\KelasRuang;
use App\Models\Nasabah;
use App\Models\Pasien;
use App\Models\PasienNasabah;
use App\Models\PenanggungRawat;
use App\Models\Registrasi;
use App\Models\RegistrasiDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DaftarGawatDaruratController extends Controller
{
    public function index()
    {
        $nasabahs = Nasabah::where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        })->orderBy('nama_nasabah')->get();

        $ruangIgd = Bagian::where('referensi_bagian_id', env('REF_BAGIAN_IGD', 3))
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->orderBy('bagian_id')
            ->get(['bagian_id', 'nama_bagian']);

        return view('moduls.Registrasi.Pendaftaran.DaftarGawatDarurat.daftar_gawat_darurat', compact('nasabahs', 'ruangIgd'));
    }

    public function store(Request $request)
    {
        $data = array_merge([
            'pasien_id' => null,
            'zona' => null,
            'dokter_id' => null,
            'nama_pengantar' => null,
            'hubungan_pengantar' => null,
            'nohp_pengantar' => null,
            'kondisi_tiba' => null,
            'icd_id' => null,
        ], $request->validate([
            'mode_pasien' => 'required|in:terdaftar,darurat',
            'pasien_id' => 'nullable|required_if:mode_pasien,terdaftar|integer|exists:pasien,pasien_id',
            'waktu_kedatangan' => 'required|date',
            'ruang_id' => 'required|integer|exists:bagian,bagian_id',
            'triase' => 'required|string|max:50',
            'cara_masuk' => 'required|string|max:50',
            'zona' => 'nullable|string|max:100',
            'dokter_id' => 'nullable|integer|exists:pegawai,pegawai_id',
            'nasabah_id' => 'required|integer|exists:nasabah,nasabah_id',
            'nama_pengantar' => 'nullable|string|max:255',
            'hubungan_pengantar' => 'nullable|string|max:50',
            'nohp_pengantar' => 'nullable|string|max:20',
            'kondisi_tiba' => 'nullable|string|max:1000',
            'icd_id' => 'nullable|integer|exists:icd,icd_id',
        ]));

        // Ruang harus milik referensi IGD
        $ruang = Bagian::where('bagian_id', $data['ruang_id'])
            ->where('referensi_bagian_id', env('REF_BAGIAN_IGD', 3))
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->first();

        if (! $ruang) {
            return back()->withInput()->with('error', 'Ruang yang dipilih bukan ruang IGD.');
        }

        // Pasien tidak boleh punya IGD / rawat aktif
        $aktif = Registrasi::where('pasien_id', $data['pasien_id'])
            ->where('jenis_rawat', env('JENIS_RAWAT_IGD', 'IGD'))
            ->whereNull('tgl_keluar')
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->exists();

        if ($aktif) {
            return back()->withInput()->with('error', 'Pasien masih memiliki pendaftaran IGD aktif.');
        }

        try {
            DB::beginTransaction();

            // Pasien Darurat (tanpa identitas) -> buat record minimal "Mr. X"
            if ($data['mode_pasien'] === 'darurat') {
                $pasien = new Pasien;
                $pasien->no_mr = GenerateHelper::generateNoMr();
                $pasien->nama_pasien = 'Pasien Darurat (Mr. X)';
                $pasien->jenis_kelamin = 'L';
                $pasien->status_batal = 0;
                $pasien->input_time = now();
                $pasien->input_user_id = auth()->id();
                $pasien->save();
            } else {
                $pasien = Pasien::where('pasien_id', $data['pasien_id'])
                    ->where(function ($q) {
                        $q->whereNull('status_batal')->orWhere('status_batal', 0);
                    })
                    ->firstOrFail();
            }

            $pasienNasabah = PasienNasabah::where('pasien_id', $pasien->pasien_id)
                ->where('nasabah_id', $data['nasabah_id'])
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->first();

            if (! $pasienNasabah) {
                $pasienNasabah = new PasienNasabah;
                $pasienNasabah->pasien_id = $pasien->pasien_id;
                $pasienNasabah->nasabah_id = $data['nasabah_id'];
                $pasienNasabah->save();
            }

            $hakKelasId = $pasienNasabah->hak_kelas_id;
            $kelasId = $hakKelasId
                ? KelasRuang::aktif()->where('kelas_ruang_id', $hakKelasId)->value('kelas_bpjs')
                : null;

            $tglMasuk = date('Y-m-d H:i:s', strtotime($data['waktu_kedatangan']));

            $memo = json_encode([
                'pengantar' => $data['nama_pengantar'] ?? null,
                'hubungan' => $data['hubungan_pengantar'] ?? null,
                'nohp' => $data['nohp_pengantar'] ?? null,
            ], JSON_UNESCAPED_UNICODE);

            $registrasi = new Registrasi;
            $registrasi->pasien_id = $pasien->pasien_id;
            $registrasi->tgl_masuk = $tglMasuk;
            $registrasi->jenis_rawat = env('JENIS_RAWAT_IGD', 'IGD');
            $registrasi->prioritas = $data['triase'];
            $registrasi->pasien_nasabah_id = $pasienNasabah->pasien_nasabah_id;
            $registrasi->memo = $memo;
            $registrasi->status_batal = 0;
            $registrasi->input_time = now();
            $registrasi->input_user_id = auth()->id();
            $registrasi->save();

            $registrasiDetail = new RegistrasiDetail;
            $registrasiDetail->registrasi_id = $registrasi->registrasi_id;
            $registrasiDetail->bagian_id = $data['ruang_id'];
            $registrasiDetail->kelas_id = $kelasId;
            $registrasiDetail->hak_kelas_id = $hakKelasId;
            $registrasiDetail->terima_dari = 'LUAR';
            $registrasiDetail->tgl_daftar = now();
            $registrasiDetail->triase = $data['triase'];
            $registrasiDetail->cara_masuk = $data['cara_masuk'];
            $registrasiDetail->lokasi_rawat = $data['zona'] ?? null;
            $registrasiDetail->ket_catatan = $data['kondisi_tiba'] ?? null;
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

            $rawatUserId = null;
            if (! empty($data['dokter_id'])) {
                $rawatUserId = User::where('pegawai_id', $data['dokter_id'])
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

            return redirect()->route('list_pasien_gawat_darurat.index')->with('success', 'Pasien berhasil didaftarkan ke IGD.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
}
