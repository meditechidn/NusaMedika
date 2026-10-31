<?php

namespace App\Http\Controllers\Registrasi\Pendaftaran\DaftarMedicalCheckup;

use App\Helpers\BillingHelper;
use App\Helpers\GenerateHelper;
use App\Http\Controllers\Controller;
use App\Models\DiagnosaRawat;
use App\Models\JadwalDokter;
use App\Models\KelasRuang;
use App\Models\Nasabah;
use App\Models\PasienNasabah;
use App\Models\PenanggungRawat;
use App\Models\Registrasi;
use App\Models\RegistrasiDetail;
use App\Models\RegistrasiUrut;
use App\Models\SuketMcu;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DaftarMedicalCheckupController extends Controller
{
    public function index()
    {
        $jadwals = JadwalDokter::aktif()
            ->whereHas('bagian', function ($q) {
                $q->aktif()
                    ->where('referensi_bagian_id', env('REF_BAGIAN_RAJAL'))
                    ->where('nama_bagian', 'ilike', '%MEDICAL CHECKUP%');
            })
            ->with([
                'pegawai' => fn ($q) => $q->where(function ($sq) {
                    $sq->whereNull('status_batal')->orWhere('status_batal', 0);
                }),
                'bagian' => fn ($q) => $q->where(function ($sq) {
                    $sq->whereNull('status_batal')->orWhere('status_batal', 0);
                }),
            ])
            ->orderBy('hari')
            ->orderBy('waktu_mulai')
            ->get();

        $polikliniks = $jadwals->map(fn ($jd) => $jd->bagian)
            ->filter()
            ->unique('bagian_id')
            ->sortBy('nama_bagian')
            ->values();

        $jadwalsByPoli = $jadwals->groupBy('bagian_id')->map(function ($group) {
            $hariMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

            return $group->map(function ($j) use ($hariMap) {
                $waktuMulai = Carbon::parse($j->waktu_mulai)->format('H:i');
                $waktuSelesai = Carbon::parse($j->waktu_selesai)->format('H:i');

                return [
                    'id' => $j->jadwal_dokter_id,
                    'dokter' => $j->pegawai->nama_pegawai ?? 'Unknown',
                    'hari' => $j->hari,
                    'nama_hari' => $hariMap[$j->hari] ?? '',
                    'waktu' => $waktuMulai.' - '.$waktuSelesai,
                    'kuota' => $j->kuota,
                ];
            })->values();
        })->toArray();

        $nasabahs = Nasabah::where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        })->orderBy('nama_nasabah')->get();

        $sukets = SuketMcu::aktif()->orderBy('nama_suket')->get();

        return view('moduls.Registrasi.Pendaftaran.DaftarMedicalCheckup.daftar_medical_checkup', compact(
            'polikliniks',
            'nasabahs',
            'jadwalsByPoli',
            'sukets'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'pasien_id' => 'required',
            'tgl_kunjungan' => 'required|date',
            'poliklinik' => 'required',
            'jadwal_dokter_id' => 'required|exists:jadwal_dokter,jadwal_dokter_id',
            'nasabah_id' => 'required',
            'cara_masuk' => 'required',
            'icd_id' => 'required',
            'keluhan' => 'required',
            'suket_mcu_id' => 'nullable|exists:suket_mcu,suket_mcu_id',
        ]);

        // Aturan "1 pasien = 1 registrasi aktif per tgl" berlaku lintas jenis_rawat (RJ & MCU).
        $aktif = Registrasi::where('pasien_id', $request->pasien_id)->whereDate('tgl_masuk', $request->tgl_kunjungan)
            ->where(function ($q) {
                $q->whereNull('status_batal')->orWhere('status_batal', 0);
            })
            ->exists();

        if ($aktif) {
            return back()->withInput()->with('error', 'Pasien telah memiliki pendaftaran aktif pada periode kunjungan '.$request->tgl_kunjungan);
        }

        $jadwal = JadwalDokter::aktif()->with('bagian')->find($request->jadwal_dokter_id);

        if (! $jadwal || ! $jadwal->bagian || $jadwal->bagian->referensi_bagian_id != env('REF_BAGIAN_RAJAL')
            || mb_strpos(mb_strtoupper($jadwal->bagian->nama_bagian), 'MEDICAL CHECKUP') === false) {
            return back()->withInput()->with('error', 'Jadwal dokter yang dipilih bukan jadwal Medical Checkup.');
        }

        $suket = $request->filled('suket_mcu_id')
            ? SuketMcu::aktif()->find($request->suket_mcu_id)
            : null;

        try {
            DB::beginTransaction();

            $pasienNasabah = PasienNasabah::where('pasien_id', $request->pasien_id)
                ->where('nasabah_id', $request->nasabah_id)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->first();

            if (! $pasienNasabah) {
                $pasienNasabah = new PasienNasabah;
                $pasienNasabah->pasien_id = $request->pasien_id;
                $pasienNasabah->nasabah_id = $request->nasabah_id;
                $pasienNasabah->save();
            }

            $hakKelasId = $pasienNasabah->hak_kelas_id;

            $kelasId = $hakKelasId
                ? KelasRuang::aktif()->where('kelas_ruang_id', $hakKelasId)->value('kelas_bpjs')
                : null;

            $registrasi = new Registrasi;
            $registrasi->pasien_id = $request->pasien_id;
            $registrasi->tgl_masuk = $request->tgl_kunjungan.' '.date('H:i:s');
            $registrasi->jenis_rawat = env('JENIS_RAWAT_MCU', 'MCU');
            $registrasi->prioritas = $request->cara_masuk;
            $registrasi->pasien_nasabah_id = $pasienNasabah->pasien_nasabah_id;
            $registrasi->memo = $request->keluhan;
            $registrasi->status_batal = 0;
            $registrasi->input_time = now();
            $registrasi->input_user_id = auth()->id();
            $registrasi->save();

            $dokter_id = $jadwal->pegawai_id;

            // Cari user_id dari dokter berdasarkan pegawai_id
            $userDokter = User::where('pegawai_id', $dokter_id)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->first();
            $rawatUserId = $userDokter ? $userDokter->user_id : auth()->id();

            $registrasiDetail = new RegistrasiDetail;
            $registrasiDetail->registrasi_id = $registrasi->registrasi_id;
            $registrasiDetail->bagian_id = $request->poliklinik;
            $registrasiDetail->kelas_id = $kelasId;
            $registrasiDetail->hak_kelas_id = $hakKelasId;
            $registrasiDetail->terima_dari = 'DALAM';
            $registrasiDetail->tgl_daftar = now();
            $registrasiDetail->suket_mcu_id = $suket ? $suket->suket_mcu_id : null;
            $registrasiDetail->harga_suket = $suket ? $suket->harga : null;
            $registrasiDetail->status_batal = 0;
            $registrasiDetail->input_time = now();
            $registrasiDetail->input_user_id = auth()->id();
            $registrasiDetail->save();

            BillingHelper::syncAdministrasi($registrasi, $registrasiDetail);
            BillingHelper::syncSuket($registrasi, $registrasiDetail);

            $tglKunjungan = Carbon::parse($request->tgl_kunjungan)->toDateString();

            $urutan = GenerateHelper::generateNoUrut($dokter_id, $request->poliklinik, $tglKunjungan);

            $estimasi = GenerateHelper::hitungEstimasi($tglKunjungan, $jadwal->waktu_mulai, $urutan);

            $registrasiUrut = new RegistrasiUrut;
            $registrasiUrut->registrasi_detail_id = $registrasiDetail->registrasi_detail_id;
            $registrasiUrut->pegawai_id = $dokter_id;
            $registrasiUrut->bagian_id = $request->poliklinik;
            $registrasiUrut->urutan = $urutan;
            $registrasiUrut->tgl_urut = $estimasi;
            $registrasiUrut->status_batal = 0;
            $registrasiUrut->input_time = now();
            $registrasiUrut->input_user_id = auth()->id();
            $registrasiUrut->save();

            $diagnosaRawat = new DiagnosaRawat;
            $diagnosaRawat->registrasi_id = $registrasi->registrasi_id;
            $diagnosaRawat->icd_id = $request->icd_id;
            $diagnosaRawat->jenis_diagnosa = 1;
            $diagnosaRawat->status_batal = 0;
            $diagnosaRawat->input_time = now();
            $diagnosaRawat->input_user_id = auth()->id();
            $diagnosaRawat->save();

            $penanggungRawat = new PenanggungRawat;
            $penanggungRawat->registrasi_id = $registrasi->registrasi_id;
            $penanggungRawat->rawat_user_id = $rawatUserId;
            $penanggungRawat->status_batal = 0;
            $penanggungRawat->input_time = now();
            $penanggungRawat->input_user_id = auth()->id();
            $penanggungRawat->save();

            DB::commit();

            return redirect()->route('list_pasien_medical_checkup.index')->with('success', 'Pendaftaran Medical Checkup berhasil.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }
}
